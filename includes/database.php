<?php
/**
 * Database Operations - Các database functions dùng chung
 */
require_once __DIR__ . '/config.php';

class DatabaseHelper {
    
    // Posts operations
    public static function getUserPosts($user_id, $limit = 10, $offset = 0) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT p.*, s.name as subject_name, s.color as subject_color 
            FROM posts p 
            LEFT JOIN subjects s ON p.subject_id = s.id 
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public static function getPostById($post_id, $user_id = null) {
        global $pdo;
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
        }
        return $stmt->fetch();
    }
    
    public static function getTotalUserPosts($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }
    
    public static function getPostsLast7Days($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM posts 
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }
    
    public static function getPostsPerDayLast7Days($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT DAYNAME(created_at) AS day_name, COUNT(*) AS count
            FROM posts
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY day_name
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    // Subjects operations
    public static function getAllSubjects() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
        return $stmt->fetchAll();
    }
    
    public static function getTotalSubjects() {
        global $pdo;
        $stmt = $pdo->query("SELECT COUNT(*) FROM subjects");
        return $stmt->fetchColumn();
    }
    
    public static function getSubjectById($subject_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        return $stmt->fetch();
    }
    
    // User operations
    public static function getUserStats($user_id) {
        global $pdo;
        $stats = [];
        
        // Total posts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_posts'] = $stmt->fetchColumn();
        
        // Posts this week
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM posts 
            WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$user_id]);
        $stats['posts_this_week'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    // Global feed
    public static function getGlobalFeed($limit = 20, $offset = 0) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, s.name as subject_name, s.color as subject_color
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN subjects s ON p.subject_id = s.id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    // Search
    public static function searchPosts($query, $limit = 20) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, s.name as subject_name
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN subjects s ON p.subject_id = s.id
            WHERE p.title LIKE ? OR p.content LIKE ?
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $search_term = "%{$query}%";
        $stmt->execute([$search_term, $search_term, $limit]);
        return $stmt->fetchAll();
    }
}
?>
