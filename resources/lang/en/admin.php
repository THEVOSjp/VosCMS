<?php

/**
 * Admin translations - English
 */

return [
    // Common
    'title' => 'Admin',
    'dashboard' => 'Dashboard',
    'back_to_site' => 'Back to Site',
    'dark_mode' => 'Toggle Dark Mode',

    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'reservations' => 'Reservations',
        'services' => 'Services',
        'categories' => 'Categories',
        'time_slots' => 'Time Slots',
        'members' => 'Members',
        'members_list' => 'Member List',
        'members_settings' => 'Member Settings',
        'members_groups' => 'Member Groups',
        'points' => 'Points',
        'users' => 'Users',
        'settings' => 'Settings',
        'site_management' => 'Site Management',
        'menu_management' => 'Menu Management',
        'design_management' => 'Design Management',
        'page_management' => 'Page Management',
    ],

    // Dashboard
    'stats' => [
        'today_reservations' => "Today's Reservations",
        'pending_reservations' => 'Pending Reservations',
        'monthly_revenue' => 'Monthly Revenue',
        'active_services' => 'Active Services',
        'total_users' => 'Total Users',
    ],

    // Reservations
    'reservations' => [
        'title' => 'Reservation Management',
        'list' => 'Reservation List',
        'calendar' => 'Calendar View',
        'statistics' => 'Statistics',
        'create' => 'Add Reservation',
        'edit' => 'Edit Reservation',
        'detail' => 'Reservation Details',

        'filter' => [
            'all' => 'All',
            'today' => 'Today',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
        ],

        'actions' => [
            'confirm' => 'Confirm',
            'cancel' => 'Cancel',
            'complete' => 'Complete',
            'no_show' => 'No Show',
            'edit' => 'Edit',
            'delete' => 'Delete',
        ],

        'confirm_msg' => 'Confirm this reservation?',
        'cancel_msg' => 'Cancel this reservation?',
        'complete_msg' => 'Mark as completed?',
        'noshow_msg' => 'Mark as no-show?',

        'success' => [
            'created' => 'Reservation created.',
            'updated' => 'Reservation updated.',
            'confirmed' => 'Reservation confirmed.',
            'cancelled' => 'Reservation cancelled.',
            'completed' => 'Reservation marked as completed.',
        ],
    ],

    // Services
    'services' => [
        'title' => 'Service Management',
        'list' => 'Service List',
        'create' => 'Add Service',
        'edit' => 'Edit Service',
        'detail' => 'Service Details',

        'fields' => [
            'name' => 'Service Name',
            'slug' => 'URL Slug',
            'description' => 'Description',
            'short_description' => 'Short Description',
            'duration' => 'Duration (min)',
            'price' => 'Price',
            'category' => 'Category',
            'is_active' => 'Active',
            'max_capacity' => 'Max Capacity',
            'buffer_time' => 'Buffer Time (min)',
            'advance_booking_days' => 'Advance Booking (days)',
            'min_notice_hours' => 'Min Notice (hours)',
        ],

        'success' => [
            'created' => 'Service created.',
            'updated' => 'Service updated.',
            'deleted' => 'Service deleted.',
            'activated' => 'Service activated.',
            'deactivated' => 'Service deactivated.',
        ],

        'error' => [
            'has_reservations' => 'Cannot delete service with existing reservations.',
        ],
    ],

    // Categories
    'categories' => [
        'title' => 'Category Management',
        'list' => 'Category List',
        'create' => 'Add Category',
        'edit' => 'Edit Category',

        'fields' => [
            'name' => 'Category Name',
            'slug' => 'URL Slug',
            'description' => 'Description',
            'parent' => 'Parent Category',
            'sort_order' => 'Sort Order',
            'is_active' => 'Active',
        ],

        'success' => [
            'created' => 'Category created.',
            'updated' => 'Category updated.',
            'deleted' => 'Category deleted.',
        ],
    ],

    // Time Slots
    'time_slots' => [
        'title' => 'Time Slot Management',
        'default_slots' => 'Default Time Slots',
        'blocked_dates' => 'Blocked Dates',

        'fields' => [
            'day_of_week' => 'Day of Week',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'max_bookings' => 'Max Bookings',
            'service' => 'Service',
            'specific_date' => 'Specific Date',
        ],

        'block_date' => 'Block Date',
        'unblock_date' => 'Unblock Date',
    ],

    // Users
    'users' => [
        'title' => 'User Management',
        'list' => 'User List',
        'create' => 'Add User',
        'edit' => 'Edit User',
        'detail' => 'User Details',

        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'role' => 'Role',
            'is_active' => 'Active',
            'created_at' => 'Registered',
            'last_login' => 'Last Login',
        ],

        'roles' => [
            'user' => 'User',
            'admin' => 'Admin',
            'super_admin' => 'Super Admin',
        ],
    ],

    // Settings
    'settings' => [
        'title' => 'System Settings',
        'general' => 'General',
        'booking' => 'Booking',
        'email' => 'Email',
        'payment' => 'Payment',

        // Settings Tabs
        'tabs' => [
            'general' => 'General',
            'seo' => 'SEO',
            'pwa' => 'PWA',
            'system' => 'System',
        ],

        // Admin Path
        'admin_path' => [
            'title' => 'Admin Access Path',
            'description' => 'Change the admin page access path for security.',
            'current_url' => 'Current Access URL',
            'label' => 'Admin Path',
            'hint' => 'Only letters, numbers, hyphens (-), and underscores (_) are allowed.',
            'warning' => 'You must access the new address after changing the path.',
            'button' => 'Change Path',
            'changed' => 'Admin path has been changed. You are now accessing via the new path.',
            'error_empty' => 'Please enter the admin path.',
            'error_invalid' => 'Admin path can only contain letters, numbers, hyphens, and underscores.',
            'error_reserved' => 'Reserved paths cannot be used.',
        ],

        // Site Settings
        'site' => [
            'title' => 'Site Settings',
            'category_label' => 'Site Category (Business Type)',
            'category_description' => 'Select the business type for the reservation system. Optimized features will be provided based on the type.',
            'category_placeholder' => '-- Select a business type --',
            'categories' => [
                'beauty_salon' => 'Beauty Salon / Hair Salon',
                'nail_salon' => 'Nail Salon',
                'skincare' => 'Skincare / Esthetics',
                'massage' => 'Massage / Spa',
                'hospital' => 'Hospital / Clinic',
                'dental' => 'Dental Clinic',
                'studio' => 'Studio / Photo Studio',
                'restaurant' => 'Restaurant / Cafe',
                'accommodation' => 'Accommodation / Hotel / Pension',
                'sports' => 'Sports / Fitness / Golf',
                'education' => 'Education / Academy / Lessons',
                'consulting' => 'Consulting / Counseling',
                'pet' => 'Pet Services / Veterinary',
                'car' => 'Auto Repair / Car Wash',
                'other' => 'Other',
            ],
            'name' => 'Site Name',
            'tagline' => 'Site Tagline',
            'tagline_hint' => 'Enter a slogan or short description for your site.',
            'url' => 'Site URL',
        ],

        // Multilingual Input
        'multilang' => [
            'button_title' => 'Multilingual Input',
            'modal_title' => 'Multilingual Input',
            'modal_description' => 'Enter content for each language.',
            'tab_ko' => 'Korean',
            'tab_en' => 'English',
            'tab_ja' => 'Japanese',
            'placeholder' => 'Enter content...',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'saved' => 'Multilingual content has been saved.',
            'error' => 'An error occurred while saving.',
        ],

        // Logo Settings
        'logo' => [
            'title' => 'Logo Settings',
            'type_label' => 'Logo Display Type',
            'type_text' => 'Text Only',
            'type_image' => 'Image Only',
            'type_image_text' => 'Image + Text',
            'image_label' => 'Logo Image',
            'current' => 'Current Logo',
            'preview' => 'New Logo Preview',
            'display_preview' => 'Display Preview',
            'hint' => 'Supports JPG, PNG, GIF, SVG, WebP (Recommended height: 40px)',
            'delete' => 'Delete',
            'delete_confirm' => 'Delete the logo image?',
        ],

        // SEO Settings
        'seo' => [
            'title' => 'SEO Settings',
            'description' => 'Manage search engine optimization settings.',

            // Meta Tags
            'meta' => [
                'title' => 'Meta Tags',
                'description_label' => 'Meta Description',
                'description_hint' => 'Description shown in search results. 150-160 characters recommended.',
                'keywords_label' => 'Meta Keywords',
                'keywords_hint' => 'Separate with commas (e.g., reservation, beauty, hair salon)',
                'keywords_placeholder' => 'reservation, beauty, hair salon, nail',
            ],

            // Open Graph
            'og' => [
                'title' => 'Social Media (Open Graph)',
                'description' => 'Set information displayed when sharing on social media.',
                'image_label' => 'Featured Image (OG Image)',
                'image_hint' => 'Recommended size: 1200x630 pixels (JPG, PNG, WebP)',
                'image_current' => 'Current Image',
                'image_preview' => 'New Image Preview',
                'image_delete' => 'Delete',
                'image_delete_confirm' => 'Delete the featured image?',
            ],

            // Search Engine
            'search_engine' => [
                'title' => 'Search Engine Settings',
                'robots_label' => 'Search Engine Visibility',
                'robots_index' => 'Allow indexing (index, follow)',
                'robots_noindex' => 'Disallow indexing (noindex, nofollow)',
                'robots_hint' => 'Set whether your site appears in search engine results.',
            ],

            // Webmaster Tools
            'webmaster' => [
                'title' => 'Webmaster Tools Verification',
                'google_label' => 'Google Search Console',
                'google_hint' => 'Enter the content value of the Google Search Console meta tag.',
                'google_placeholder' => 'XXXXXXXXXXXXXXXX',
                'naver_label' => 'Naver Webmaster Tools',
                'naver_hint' => 'Enter the content value of the Naver Webmaster Tools meta tag.',
                'naver_placeholder' => 'XXXXXXXXXXXXXXXX',
            ],

            // Analytics
            'analytics' => [
                'title' => 'Analytics Integration',
                'ga_label' => 'Google Analytics Tracking ID',
                'ga_hint' => 'Enter in G-XXXXXXXXXX or UA-XXXXXXXXX-X format.',
                'ga_placeholder' => 'G-XXXXXXXXXX',
                'gtm_label' => 'Google Tag Manager ID',
                'gtm_hint' => 'Enter in GTM-XXXXXXX format.',
                'gtm_placeholder' => 'GTM-XXXXXXX',
            ],

            // Save Message
            'success' => 'SEO settings saved.',
        ],

        // PWA Settings
        'pwa' => [
            'title' => 'PWA Settings',
            'description' => 'Manage Progressive Web App (PWA) settings.',

            // Frontend PWA
            'front' => [
                'title' => 'Frontend PWA',
                'description' => 'Web app settings for users.',
                'name_label' => 'App Name',
                'name_placeholder' => 'Enter app name',
                'short_name_label' => 'Short Name',
                'short_name_placeholder' => 'Short name',
                'short_name_hint' => 'Max 12 characters. Shown on home screen when space is limited.',
                'description_label' => 'App Description',
                'theme_color_label' => 'Theme Color',
                'bg_color_label' => 'Background Color',
                'display_label' => 'Display Mode',
                'icon_label' => 'App Icon',
            ],

            // Admin PWA
            'admin' => [
                'title' => 'Admin PWA',
                'description' => 'Web app settings for administrators.',
                'name_label' => 'App Name',
                'short_name_label' => 'Short Name',
                'theme_color_label' => 'Theme Color',
                'bg_color_label' => 'Background Color',
                'icon_label' => 'App Icon',
            ],

            // Common
            'icon_current' => 'Current Icon',
            'icon_hint' => 'PNG or WebP format, 512x512 pixels recommended',
            'icon_delete' => 'Delete Icon',
            'icon_delete_confirm' => 'Are you sure you want to delete this icon?',
            'icon_deleted' => 'Icon has been deleted.',
            'error_icon_type' => 'Invalid image format. Only PNG or WebP allowed.',
            'success' => 'PWA settings saved.',
        ],

        // System Information
        'system' => [
            // Tab Menu
            'tabs' => [
                'info' => 'Information',
                'cache' => 'Cache',
                'mode' => 'Mode',
                'logs' => 'Logs',
                'updates' => 'Updates',
            ],
            'app' => [
                'title' => 'Application Information',
                'name' => 'App Name',
                'version' => 'Version',
                'environment' => 'Environment',
                'debug_mode' => 'Debug Mode',
                'debug_warning' => 'Disable debug mode in production environment.',
                'url' => 'URL',
                'locale' => 'Locale',
            ],
            'php' => [
                'title' => 'PHP Information',
                'version' => 'PHP Version',
                'sapi' => 'SAPI',
                'timezone' => 'Timezone',
                'memory_limit' => 'Memory Limit',
                'max_execution_time' => 'Max Execution Time',
                'upload_max_filesize' => 'Max Upload Size',
                'post_max_size' => 'Max POST Size',
                'display_errors' => 'Display Errors',
                'extensions' => 'Required Extensions',
            ],
            'db' => [
                'title' => 'Database Information',
                'driver' => 'Driver',
                'version' => 'Version',
                'host' => 'Host',
                'database' => 'Database',
                'charset' => 'Charset',
                'collation' => 'Collation',
            ],
            'server' => [
                'title' => 'Server Information',
                'os' => 'Operating System',
                'os_family' => 'OS Family',
                'software' => 'Server Software',
                'document_root' => 'Document Root',
                'current_time' => 'Current Time',
            ],
            'status' => [
                'on' => 'On',
                'off' => 'Off',
            ],
            // Cache Management
            'cache' => [
                'title' => 'Cache Management',
                'description' => 'Manage application cache. Clearing cache may temporarily affect performance.',
                'view' => 'View Cache',
                'view_desc' => 'Compiled view template cache',
                'config' => 'Config Cache',
                'config_desc' => 'Application configuration cache',
                'route' => 'Route Cache',
                'route_desc' => 'Routing information cache',
                'clear' => 'Clear',
                'clear_all' => 'Clear All Cache',
                'cached' => 'Cached',
                'not_cached' => 'None',
                'confirm_clear' => 'Are you sure you want to clear the cache?',
                'cleared' => 'Cache has been cleared.',
            ],
            // Mode Management
            'mode' => [
                'title' => 'Mode Management',
                'description' => 'Manage application runtime modes.',
                'debug' => 'Debug Mode',
                'debug_desc' => 'Shows detailed error messages. Disable in production.',
                'maintenance' => 'Maintenance Mode',
                'maintenance_desc' => 'Blocks user access during site maintenance.',
                'environment' => 'Environment',
                'environment_desc' => 'Current application runtime environment',
                'env_notice' => 'Debug mode and environment can be changed in the .env file.',
                'enable_maintenance' => 'Enable Maintenance Mode',
                'disable_maintenance' => 'Disable Maintenance Mode',
                'confirm_enable_maintenance' => 'Enable maintenance mode? All users except administrators will be blocked from accessing the site.',
                'confirm_disable_maintenance' => 'Disable maintenance mode?',
                'maintenance_enabled' => 'Maintenance mode has been enabled.',
                'maintenance_disabled' => 'Maintenance mode has been disabled.',
                'maintenance_message' => 'The site is currently under maintenance. Please visit again later.',
                // Debug mode toggle
                'enable_debug' => 'Enable Debug Mode',
                'disable_debug' => 'Disable Debug Mode',
                'confirm_enable_debug' => 'Enable debug mode? Error details will be displayed.',
                'confirm_disable_debug' => 'Disable debug mode?',
                'debug_enabled' => 'Debug mode has been enabled.',
                'debug_disabled' => 'Debug mode has been disabled.',
                'debug_error' => 'An error occurred while setting debug mode.',
                'debug_env_locked' => 'Cannot disable because APP_DEBUG=true is set in .env file.',
            ],
            // Log Management
            'logs' => [
                'title' => 'Log Management',
                'description' => 'Manage application log files.',
                'filename' => 'Filename',
                'size' => 'Size',
                'modified' => 'Modified',
                'actions' => 'Actions',
                'view' => 'View',
                'delete' => 'Delete',
                'download' => 'Download',
                'copy' => 'Copy',
                'copied' => 'Copied to clipboard.',
                'clear_all' => 'Clear All Logs',
                'no_logs' => 'No log files found.',
                'no_logs_desc' => 'No logs have been recorded yet.',
                'confirm_delete' => 'Are you sure you want to delete this log file?',
                'confirm_clear_all' => 'Are you sure you want to delete all log files? This action cannot be undone.',
                'deleted' => 'Log file has been deleted.',
                'all_cleared' => 'All log files have been deleted.',
                'back_to_list' => 'Back to list',
                'selected' => 'selected',
                'delete_selected' => 'Delete Selected',
                'confirm_delete_selected' => 'Are you sure you want to delete selected log files?',
                'selected_deleted' => ':count log file(s) have been deleted.',
                'total_files' => 'Total :count file(s)',
                'last_lines' => 'Showing last :count lines',
                'showing_first' => 'Showing :count of :total files.',
            ],
            // Update Management
            'updates' => [
                'title' => 'Update Management',
                'description' => 'Manage system updates via GitHub.',
                'current_version' => 'Current Version',
                'channel' => 'Channel',
                'check_update' => 'Check for Updates',
                'checking' => 'Checking...',
                'check_failed' => 'Failed to check for updates.',
                'up_to_date' => 'You are using the latest version.',
                'new_version_available' => 'New version available!',
                'view_details' => 'View Details',
                'release_notes' => 'Release Notes',
                'no_notes' => 'No release notes available.',
                'no_releases' => 'No releases found.',
                'requirements' => 'System Requirements',
                'writable_root' => 'Root Directory Writable',
                'writable_storage' => 'Storage Directory Writable',
                'not_available' => 'N/A',
                'requirements_warning' => 'Some requirements are not met. Auto-update may be limited.',
                'notes_title' => 'Update Notes',
                'note_backup' => 'A backup is automatically created before updating.',
                'note_maintenance' => 'Site goes into maintenance mode during update.',
                'note_rollback' => 'Automatic rollback on update failure.',
                // Backup related
                'backups' => 'Backups',
                'no_backups' => 'No backups available.',
                'restore' => 'Restore',
                'confirm_restore' => 'Restore from this backup? Current files will be overwritten.',
                'restore_failed' => 'Failed to restore.',
                // Update execution
                'update_now' => 'Update Now',
                'confirm_update' => 'Proceed with update? A backup will be created automatically.',
                'update_failed' => 'Update failed.',
                'reload_page' => 'Page will refresh shortly.',
            ],
        ],

        // System Info (legacy)
        'system_info' => [
            'title' => 'System Information',
            'php_version' => 'PHP Version',
            'environment' => 'Environment',
            'timezone' => 'Timezone',
            'debug_mode' => 'Debug Mode',
            'enabled' => 'Enabled',
            'disabled' => 'Disabled',
        ],

        'fields' => [
            'app_name' => 'Site Name',
            'app_timezone' => 'Timezone',
            'app_locale' => 'Default Language',
            'admin_path' => 'Admin Path',
            'booking_auto_confirm' => 'Auto Confirm Bookings',
            'booking_email_notification' => 'Email Notifications',
            'booking_advance_days' => 'Advance Booking (days)',
        ],

        'success' => 'Site settings saved.',
        'error_save' => 'Save failed',
        'error_image_type' => 'Invalid image format. (Only JPG, PNG, GIF, SVG, WebP allowed)',
        'logo_deleted' => 'Logo image has been deleted.',
    ],

    // Site Management
    'site' => [
        // Menu Management
        'menus' => [
            'title' => 'Menu Management',
            'description' => 'Manage site navigation menus.',
            'list' => 'Menu List',
            'add' => 'Add Menu',
            'coming_soon' => 'Menu management coming soon',
            'coming_soon_desc' => 'You will be able to manage navigation menus soon.',
        ],

        // Design Management
        'design' => [
            'title' => 'Design Management',
            'description' => 'Manage site design and themes.',
            'theme_title' => 'Theme Settings',
            'theme_desc' => 'Change the site color theme and style.',
            'layout_title' => 'Layout Settings',
            'layout_desc' => 'Change page layout and structure.',
            'header_footer_title' => 'Header/Footer',
            'header_footer_desc' => 'Change header and footer design.',
            'coming_soon' => 'Coming Soon',
        ],

        // Page Management
        'pages' => [
            'title' => 'Page Management',
            'description' => 'Create and manage custom pages.',
            'list' => 'Page List',
            'add' => 'New Page',
            'system_page' => 'System Page',
            'custom_page' => 'Custom Page',
            'empty' => 'No custom pages yet.',
            'empty_hint' => 'Add new pages to expand your site.',
            'home' => 'Home',
            'terms' => 'Terms of Service',
            'privacy' => 'Privacy Policy',
        ],
    ],

    // Common Buttons
    'buttons' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'add' => 'Add',
        'create' => 'Create',
        'update' => 'Update',
        'search' => 'Search',
        'reset' => 'Reset',
        'confirm' => 'Confirm',
        'back' => 'Back',
        'close' => 'Close',
    ],

    // Common Messages
    'messages' => [
        'confirm_delete' => 'Are you sure you want to delete?',
        'no_data' => 'No data available.',
        'loading' => 'Loading...',
        'processing' => 'Processing...',
    ],

    // Common Text
    'common' => [
        'yes' => 'Yes',
        'no' => 'No',
        'recommended' => 'Recommended',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'actions' => 'Actions',
        'showing' => 'Showing',
        'of' => 'of',
        'prev' => 'Previous',
        'next' => 'Next',
    ],

    // Member Management
    'members' => [
        'title' => 'Member Management',
        'list' => 'Member List',
        'create' => 'Add Member',
        'edit' => 'Edit Member',
        'detail' => 'Member Details',

        // Member Settings
        'settings' => [
            'title' => 'Member Settings',

            // Tab Menu
            'tabs' => [
                'general' => 'General',
                'features' => 'Features',
                'terms' => 'Terms',
                'register' => 'Registration',
                'login' => 'Login',
                'design' => 'Design',
            ],

            // General Settings
            'general' => [
                'title' => 'General Settings',
                'description' => 'Configure basic member system behavior.',

                // Registration Mode
                'registration_mode' => 'Allow Registration',
                'registration_mode_desc' => 'Choose whether to accept member registration. If using URL key, registration is only possible when accessing with a URL containing the matching string.',
                'registration_url_key' => 'Allow only with matching URL key',
                'url_key_label' => 'URL Key',
                'url_key_placeholder' => 'e.g., secretkey123',
                'url_key_hint' => 'Registration URL must include ?key=value format. (e.g., /register?key=secretkey123)',

                // Email Verification
                'email_verification' => 'Use Email Verification',
                'email_verification_desc' => 'Send verification email to confirm membership. Users must click the verification link to login normally.',

                // Email Validity
                'email_validity' => 'Email Validity Period',
                'email_validity_desc' => 'Limit the validity period of registration verification emails, account recovery emails, etc.',
                'days' => 'days',

                // Show Profile Photo
                'show_profile_photo' => 'Show Member Profile Photo',
                'show_profile_photo_desc' => 'Option to view profile images on admin member list page. Select No if you don\'t want to see profile photos in the member list.',

                // Logout on Password Change
                'logout_on_password_change' => 'Logout Other Devices on Password Change',
                'logout_on_password_change_desc' => 'When password is changed, logout all other sessions except the current device (browser).',

                // Password Recovery Method
                'password_recovery_method' => 'ID/PW Recovery Method',
                'password_recovery_method_desc' => 'Select the method for changing password when using ID/PW recovery feature.',
                'recovery_link' => 'Send password change page link',
                'recovery_random' => 'Send random password',

                // Member Info Sync
                'sync_title' => 'Member Info Sync',
                'sync_description' => 'Synchronize member information with posts/comments.',
                'sync_warning' => 'This may take a long time if there is a lot of data. Make sure to stop the service during maintenance if there are many users.',
                'sync_button' => 'Run Sync',
                'sync_confirm' => 'Proceed with member info sync? This may take a long time if there is a lot of data.',
                'sync_complete' => 'Member info sync completed.',
                'sync_error' => 'Error occurred during sync',

                // Legacy Settings
                'auto_login' => 'Auto Login After Registration',
                'auto_login_desc' => 'Automatically log in after registration.',
                'default_group' => 'Default Member Group',
                'default_group_desc' => 'Group automatically assigned to new members',
                'groups' => [
                    'member' => 'Regular Member',
                    'vip' => 'VIP Member',
                    'pending' => 'Pending Approval',
                ],
            ],

            // Feature Settings
            'features' => [
                'title' => 'Feature Settings',
                'description' => 'Configure features available to members.',
                'view_scrap' => 'View Scraps',
                'view_scrap_desc' => 'Allow viewing scrapped content.',
                'view_bookmark' => 'View Bookmarks',
                'view_bookmark_desc' => 'Allow viewing bookmarked content.',
                'view_posts' => 'View Posts',
                'view_posts_desc' => 'Allow viewing written posts.',
                'view_comments' => 'View Comments',
                'view_comments_desc' => 'Allow viewing written comments.',
                'auto_login_manage' => 'Auto Login Management',
                'auto_login_manage_desc' => 'Allow members to manage auto-login devices.',
            ],

            // Terms Settings
            'terms' => [
                'title' => 'Terms Settings',
                'description' => 'Set up terms to be agreed to during registration. Up to 5 terms can be registered.',
                'term_section' => 'Membership Terms',
                'term_title' => 'Terms Title',
                'term_title_placeholder' => 'e.g., Terms of Service, Privacy Policy',
                'term_content' => 'Terms Content',
                'consent_required' => 'Consent Required',
                'consent_required_option' => 'Required',
                'consent_optional_option' => 'Optional',
                'consent_disabled_option' => 'Disabled',
            ],

            // Registration Settings
            'register' => [
                'title' => 'Registration Settings',
                'description' => 'Configure registration form and process.',
                'form_fields' => 'Registration Form Fields',
                'form_fields_desc' => 'Select fields to collect during registration.',
                'required_note' => '* marked fields are required.',
                'fields' => [
                    'name' => 'Name',
                    'email' => 'Email',
                    'password' => 'Password',
                    'phone' => 'Phone',
                    'birth_date' => 'Birth Date',
                    'gender' => 'Gender',
                    'company' => 'Company/Organization',
                    'blog' => 'Blog',
                    'profile_photo' => 'Profile Photo',
                ],
                'use_captcha' => 'Use CAPTCHA',
                'use_captcha_desc' => 'Display CAPTCHA to prevent bot registration.',
                'email_provider' => 'Email Provider Management',
                'email_provider_desc' => 'Allow registration only from specific email domains, or block certain domains. (e.g., naver.com, gmail.com)',
                'email_provider_none' => 'No restrictions',
                'email_provider_allow' => 'Allow',
                'email_provider_block' => 'Block',
                'email_provider_placeholder' => 'e.g., naver.com, gmail.com',
                'email_provider_hint' => 'Add items one by one. If nothing is entered, email addresses are not restricted.',
                'email_provider_invalid' => 'Invalid domain format.',
                'email_provider_duplicate' => 'This domain is already registered.',
                'welcome_email' => 'Send Welcome Email',
                'welcome_email_desc' => 'Send welcome email after registration.',
                'redirect_url' => 'Post-Registration Redirect URL',
                'redirect_url_desc' => 'Enter the URL to redirect to after registration.',
                'redirect_url_placeholder' => 'e.g., /welcome or https://example.com/welcome',
                'redirect_url_hint' => 'Leave empty to redirect to the default page (My Page or Home).',
            ],

            // Login Settings
            'login' => [
                'title' => 'Login Settings',
                'description' => 'Configure login method and security options.',
                'method' => 'Login Method',
                'method_desc' => 'Select the ID type for member login.',
                'method_email' => 'Email',
                'method_phone' => 'Phone',
                'method_both' => 'Email or Phone',
                'remember_me' => 'Remember Me Option',
                'remember_me_desc' => 'Display remember me checkbox.',
                'attempts' => 'Login Attempt Limit',
                'attempts_desc' => 'Number of failed attempts allowed before account lockout',
                'unlimited' => 'Unlimited',
                'times' => 'times',
                'lockout' => 'Account Lockout Duration',
                'lockout_desc' => 'Duration of account lockout after failed login attempts',
                'minutes' => 'minutes',
                'hour' => 'hour',
                'hours' => 'hours',
                'seconds' => 'seconds',
                'brute_force' => 'Brute Force Protection',
                'brute_force_desc' => 'Limit the number of login attempts from a single IP address within a short period.',
                'single_device' => 'Single Device Login',
                'single_device_desc' => 'Allow login from only one device at a time.',
                'login_redirect_url' => 'Login Redirect URL',
                'login_redirect_url_desc' => 'Set the URL to redirect after login. If empty, returns to the previous page.',
                'logout_redirect_url' => 'Logout Redirect URL',
                'logout_redirect_url_desc' => 'Set the URL to redirect after logout. If empty, returns to the previous page.',
                'redirect_url_placeholder' => 'e.g., /mypage or https://example.com',
            ],

            // Design Settings
            'design' => [
                'title' => 'Design Settings',
                'description' => 'Configure design for member-related pages.',
                'mypage_layout' => 'My Page Layout',
                'layout_width' => 'Total Width',
                'layout_left_width' => 'Left Width',
                'layout_content_width' => 'Content Width',
                'layout_right_width' => 'Right Width',
                'layout_style' => 'Layout Style',
                'layout' => 'Layout',
                'layout_desc' => 'Select the layout for member pages.',
                'layout_none' => 'Not Used',
                'layout_basic' => 'Basic Layout',
                'layout_sidebar_type' => 'Sidebar Layout',
                'no_layouts_found' => 'No layouts found in the layouts folder. Please add layout folders.',
                'skin' => 'Skin',
                'skin_desc' => 'Select the skin for member pages.',
                'skin_default' => 'Member Default Skin (default)',
                'skin_modern' => 'Modern Skin',
                'skin_classic' => 'Classic Skin',
                'colorset' => 'Color Set',
                'colorset_desc' => 'Select the color theme for the skin.',
                'colorset_default' => 'Default',
                'colorset_blue' => 'Blue',
                'colorset_green' => 'Green',
                'colorset_purple' => 'Purple',
                'mobile_layout' => 'Mobile Layout',
                'mobile_layout_desc' => 'Select the layout for mobile devices.',
                'mobile_responsive' => 'Use Same Responsive Layout as PC',
                'mobile_skin' => 'Mobile Skin',
                'mobile_skin_desc' => 'Select the skin for mobile devices.',
                'mobile_skin_responsive' => 'Use Same Responsive Skin as PC',
                'mobile_skin_mobile' => 'Mobile-Only Skin',
                'form_style' => 'Form Style',
                'form_style_desc' => 'Select the style for login/registration forms.',
                'style_default' => 'Default',
                'style_card' => 'Card',
                'style_minimal' => 'Minimal',
                'login_background' => 'Login Page Background',
                'login_background_desc' => 'Background style for login page',
                'bg_none' => 'None (Default)',
                'bg_gradient' => 'Gradient',
                'bg_image' => 'Image',
                'bg_pattern' => 'Pattern',
                'register_layout' => 'Registration Page Layout',
                'register_layout_desc' => 'Layout for registration form',
                'layout_single' => 'Single Page',
                'layout_steps' => 'Step by Step',
                'layout_split' => 'Split View',
                'social_login' => 'Social Login',
                'social_login_desc' => 'Display social login buttons on login/registration pages.',
                'social_google' => 'Google Login',
                'social_line' => 'LINE Login',
                'social_kakao' => 'KakaoTalk Login',
                'profile_style' => 'Profile Page Style',
                'profile_style_desc' => 'Layout style for member profile page',
                'profile_card' => 'Card',
                'profile_sidebar' => 'Sidebar',
                'profile_tabs' => 'Tabs',

                // Skin Management
                'add_skin' => 'Add New Skin',
                'direct_register' => 'Direct Upload',
                'direct_register_desc' => 'Upload skin files directly',
                'marketplace' => 'From Marketplace',
                'marketplace_desc' => 'Browse various skins',
            ],
        ],

        // Member Groups
        'groups' => [
            'title' => 'Member Groups',
            'list' => 'Group List',
            'create' => 'Add Group',
            'edit' => 'Edit Group',
            'fields' => [
                'name' => 'Group Name',
                'description' => 'Description',
                'discount_rate' => 'Discount Rate (%)',
                'point_rate' => 'Point Rate (%)',
                'is_default' => 'Default Group',
            ],
        ],

        // Points
        'points' => [
            'title' => 'Points Management',
            'history' => 'Points History',
            'add' => 'Add Points',
            'deduct' => 'Deduct Points',
            'fields' => [
                'member' => 'Member',
                'amount' => 'Amount',
                'reason' => 'Reason',
                'balance' => 'Balance',
            ],
        ],
    ],
];
