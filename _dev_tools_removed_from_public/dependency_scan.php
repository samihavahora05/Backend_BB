<?php

$tables = [
    'backup_histories', 'banners', 'blog_blog_tag', 'blog_categories', 'blog_category_blog', 'blog_tag_pivot', 
    'college_company_partnerships', 'college_students', 'company_documents', 'company_gallery', 'company_members', 
    'company_social_links', 'contact_messages', 'contest_winners', 'course_categories', 'course_certificates', 
    'course_reviews', 'crm_notes', 'dashboard_statistics', 'departments', 'expert_availabilities', 'expert_bookings', 
    'expert_certifications', 'expert_social_links', 'expert_specializations', 'followups', 'hero_banners', 
    'hero_sections', 'homepage_sections', 'interview_feedback', 'interview_rounds', 'job_batches', 'lead_notes', 
    'lead_statuses', 'media', 'media_categories', 'media_favorites', 'media_file_tag', 'media_logs', 'media_permissions', 
    'media_share_links', 'media_versions', 'model_has_permissions', 'notification_reads', 'page_views', 'permissions', 
    'placement_officers', 'quiz_attempts', 'quizzes', 'recruiter_visits', 'resume_downloads', 'resume_templates', 
    'resume_versions', 'role_has_permissions', 'scholarship_documents', 'scholarship_reviews', 'scholarship_status_history', 
    'search_logs', 'success_stories', 'uploaded_files', 'virtual_classes', 'visitor_logs', 'wishlist_courses'
];

$scanDirs = [
    'D:/blueboxx/blueboxx web/backend/app',
    'D:/blueboxx/blueboxx web/backend/database',
    'D:/blueboxx/blueboxx web/backend/routes',
    'D:/blueboxx/blueboxx web/src',
    'D:/blueboxx/blueboxx web/pages',
];

$results = [];
foreach ($tables as $t) {
    $results[$t] = [];
}

$exts = ['php', 'js', 'jsx', 'ts', 'tsx'];

foreach ($scanDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (in_array($ext, $exts)) {
                $content = file_get_contents($file->getPathname());
                foreach ($tables as $t) {
                    if (preg_match("/\b" . preg_quote($t, '/') . "\b/", $content)) {
                        $relPath = str_replace('D:/blueboxx/blueboxx web/', '', str_replace('\\', '/', $file->getPathname()));
                        $results[$t][] = $relPath;
                    }
                }
            }
        }
    }
}

$md = "# Dependency Analysis for Potentially Unused Tables\n\n";

$trulyUnused = [];
$referenced = [];

foreach ($tables as $t) {
    if (count($results[$t]) > 0) {
        $referenced[$t] = array_unique($results[$t]);
    } else {
        $trulyUnused[] = $t;
    }
}

$md .= "## 1. Tables WITH Dependencies (DO NOT DELETE)\n";
$md .= "These tables were found to be referenced in Models, Controllers, Migrations, or Frontend code:\n\n";

foreach ($referenced as $t => $files) {
    $md .= "### `$t` (Referenced in " . count($files) . " files)\n";
    $shown = array_slice($files, 0, 10);
    foreach ($shown as $f) {
        $md .= "- `$f`\n";
    }
    if (count($files) > 10) {
        $md .= "- *(...and " . (count($files) - 10) . " more)*\n";
    }
    $md .= "\n";
}

$md .= "## 2. Truly Unused Tables (ZERO references)\n";
$md .= "These tables have 0 rows, no models, and no string references anywhere in the codebase.\n\n";

if (count($trulyUnused) === 0) {
    $md .= "No tables are truly unused. All 63 tables have at least one reference.\n";
} else {
    foreach ($trulyUnused as $t) {
        $md .= "- `$t`\n";
    }
}

header('Content-Type: text/plain');
echo $md;
