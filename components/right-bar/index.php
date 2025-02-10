<?php
include("components/trend-card.php");

function getTrendingCategories($link, $interval = null) {
    $timeConstraint = $interval 
        ? "WHERE post_date >= NOW() - INTERVAL 1 $interval" 
        : "";
    
    $query = "
        SELECT 
            COUNT(post_id) as post_count,
            category.category_id,
            category.category_title
        FROM post
        INNER JOIN category ON post.post_category_id = category.category_id
        $timeConstraint
        GROUP BY post_category_id
        ORDER BY COUNT(post_id) DESC
        LIMIT 3
    ";
    
    $stmt = $link->prepare($query);
    $stmt->execute();
    return $stmt->get_result();
}

// Time periods to check, in order of priority
$timePeriods = [
    'day' => 'Today\'s Trends',
    'week' => 'Weekly Trends',
    'month' => 'Monthly Trends',
    'year' => 'Yearly Trends'
];

// Try each time period until we find one with enough data
foreach ($timePeriods as $period => $title) {
    $trendData = getTrendingCategories($link, $period);
    if ($trendData->num_rows == 3) {
        echo TrendCard($trendData, $title);
        exit();
    }
}

// Fallback to all-time trends if no period has enough data
$trendData = getTrendingCategories($link);
echo TrendCard($trendData, 'Trending Categories');
?>