<?php
/**
 * Helper Functions - Các functions dùng chung cho toàn project
 */
require_once __DIR__ . '/config.php';

// Format thời gian relative
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Validate file upload
function validate_image_upload($file, $max_size = 5242880) {
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if ($file['size'] > $max_size) {
        return ['error' => 'File too large. Max size: 5MB'];
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return ['error' => 'Invalid file type. Only: ' . implode(', ', $allowed_types)];
    }
    
    return ['success' => true];
}

// Generate avatar URL
function get_avatar_url($avatar = null, $username = '') {
    if (!empty($avatar)) {
        return '../uploads/avatars/' . $avatar;
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=4f46e5&color=fff';
}

// Clean input để prevent XSS
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Pagination helper
function get_pagination($total_items, $items_per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $items_per_page
    ];
}
?>
