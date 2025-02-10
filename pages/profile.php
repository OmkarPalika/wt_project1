<?php
include("../scripts/format_date.php");
require_once "../scripts/db_connect.php";
session_start();

if (!isset($_GET["user_id"])) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_SESSION["loggedin"])) {
    echo json_encode(["status" => "error", "message" => "You must be logged in."]);
    exit;
}

// Follow/Unfollow logic using AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === "follow") {
    if ($user_id && $_GET["user_id"] != $user_id) {
        $receiver_id = intval($_GET["user_id"]);

        // Check cache first
        $cacheKey = "follow:$user_id:$receiver_id";
        $isFollowing = $redis->get($cacheKey);

        if ($isFollowing === false) {
            // Not in cache, check the database
            $stmt = $link->prepare("SELECT * FROM user_follow WHERE sender_id = ? AND receiver_id = ?");
            $stmt->bind_param("ii", $user_id, $receiver_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $isFollowing = $result->num_rows >= 1;
            $stmt->close();
        }

        if ($isFollowing) {
            // Unfollow
            $stmt = $link->prepare("DELETE FROM user_follow WHERE sender_id = ? AND receiver_id = ?");
            $stmt->bind_param("ii", $user_id, $receiver_id);
            $stmt->execute();
            $stmt->close();
            $redis->del($cacheKey);
            echo json_encode(["status" => "unfollowed"]);
        } else {
            // Follow
            $stmt = $link->prepare("INSERT INTO user_follow (sender_id, receiver_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $receiver_id);
            $stmt->execute();
            $stmt->close();
            $redis->setex($cacheKey, 3600, true); // Cache for 1 hour
            echo json_encode(["status" => "followed"]);

            // WebSocket event trigger (to be implemented)
        }
    }
    exit;
}

// Like/Dislike logic using AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && in_array($_POST['action'], ["like", "dislike"])) {
    if ($user_id) {
        $post_id = intval($_POST['post_id']);
        $actionType = $_POST['action'] === "like" ? 1 : -1;

        // Check cache first
        $cacheKey = "post_review:$user_id:$post_id";
        $currentReview = $redis->get($cacheKey);

        if ($currentReview === false) {
            $stmt = $link->prepare("SELECT post_review_type FROM post_review WHERE post_review_user_id = ? AND post_review_post_id = ?");
            $stmt->bind_param("ii", $user_id, $post_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $reviewData = $result->fetch_assoc();
            $currentReview = $reviewData["post_review_type"] ?? 0;
            $stmt->close();
        }

        if ($currentReview == $actionType) {
            // Remove like/dislike
            $stmt = $link->prepare("DELETE FROM post_review WHERE post_review_user_id = ? AND post_review_post_id = ?");
            $stmt->bind_param("ii", $user_id, $post_id);
            $stmt->execute();
            $stmt->close();
            $redis->del($cacheKey);
            echo json_encode(["status" => "removed"]);
        } else {
            // Like/Dislike the post
            if ($currentReview == 0) {
                $stmt = $link->prepare("INSERT INTO post_review (post_review_user_id, post_review_post_id, post_review_type) VALUES (?, ?, ?)");
            } else {
                $stmt = $link->prepare("UPDATE post_review SET post_review_type = ? WHERE post_review_user_id = ? AND post_review_post_id = ?");
            }
            $stmt->bind_param("iii", $actionType, $user_id, $post_id);
            $stmt->execute();
            $stmt->close();
            $redis->setex($cacheKey, 3600, $actionType); // Cache for 1 hour
            echo json_encode(["status" => $actionType == 1 ? "liked" : "disliked"]);
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-50">
    <?php include("../components/header.php"); ?>
    <div class="p-2 container mx-auto grid grid-cols-12 gap-4">
        <div class="col-span-3">
            <?php include("../components/left-bar/components/recommended-user.php"); ?>
        </div>
        <div class="col-span-6">
            <?php include("../components/profile/index.php"); ?>
        </div>
        <div class="col-span-3">
            <?php include("../components/right-bar/index.php"); ?>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $(".follow-btn").click(function() {
                let userId = $(this).data("user-id");
                $.post("", { action: "follow", user_id: userId }, function(response) {
                    let res = JSON.parse(response);
                    if (res.status === "followed") {
                        $(".follow-btn").text("Unfollow");
                    } else {
                        $(".follow-btn").text("Follow");
                    }
                });
            });

            $(".like-btn, .dislike-btn").click(function() {
                let postId = $(this).data("post-id");
                let actionType = $(this).hasClass("like-btn") ? "like" : "dislike";
                $.post("", { action: actionType, post_id: postId }, function(response) {
                    let res = JSON.parse(response);
                    if (res.status === "liked") {
                        $(".like-btn").addClass("text-blue-500");
                        $(".dislike-btn").removeClass("text-red-500");
                    } else if (res.status === "disliked") {
                        $(".dislike-btn").addClass("text-red-500");
                        $(".like-btn").removeClass("text-blue-500");
                    } else {
                        $(".like-btn, .dislike-btn").removeClass("text-blue-500 text-red-500");
                    }
                });
            });
        });
    </script>
</body>
</html>
