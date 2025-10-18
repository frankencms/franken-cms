<?php

return [
    'settings' => [
        'label'   => 'Settings',
        'general' => [
            'title'       => 'Settings',
            'description' => 'General settings for the site',
            'form'        => [
                'title' => [
                    // General
                    'label'  => 'Site Title',
                    'helper' => 'The Site Title is what you see in the browser tab and in the header of the admin panel',
                ],
                'icon' => [
                    'label'  => 'Site Icon',
                    'helper' => 'The Site Icon is what you see in browser tabs, bookmark bars, and within the mobile apps. It should be square and at least 512 by 512 pixels',
                ],
                'membership' => [
                    'label'  => 'Membership',
                    'helper' => 'Anyone can register',
                ],
                'default_user_role' => [
                    'label' => 'New User Default Role',
                ],
                'timezone' => [
                    'label'  => 'Timezone',
                    'helper' => 'Choose either a city in the same timezone as you or a UTC (Coordinated Universal Time) time offset.',
                ],
                'date_format' => [
                    'label' => 'Date Format',
                ],
                'custom_date_format' => [
                    'label' => 'Custom Date Format',
                ],
                'time_format' => [
                    'label'  => 'Time Format',
                    'helper' => 'Choose a time format that will be used throughout the site.',
                ],
                'custom_time_format' => [
                    'label' => 'Custom Time Format',
                ],
                'week_starts_on' => [
                    'label' => 'Week Starts On',
                ],
                // Reading
                '',

            ],
        ],

        'writing' => [
            'title'       => 'Writing',
            'description' => 'Settings for writing and publishing content',
            'form'        => [
                'posts_per_page' => [
                    'label' => 'Blog pages show at most',
                ],
                'posts_per_rss' => [
                    'label' => 'Syndication feeds show the most recent',
                ],
                'include_in_feed' => [
                    'label' => 'For each article in a feed, show',
                ],
                'discourage_search_visibility' => [
                    'label' => 'Search Engine Visibility',
                ],
            ],
        ],
        'reading' => [
            'title'       => 'Reading',
            'description' => 'Settings for reading and displaying content',
            'form'        => [
                'posts_per_page' => [
                    'label' => 'Blog pages show at most',
                ],
                'posts_per_rss' => [
                    'label' => 'Syndication feeds show the most recent',
                ],
                'include_in_feed' => [
                    'label' => 'For each article in a feed, show',
                ],
                'discourage_search_visibility' => [
                    'label' => 'Search Engine Visibility',
                ],
            ],
        ],

        'discussion' => [
            'title'       => 'Discussion',
            'description' => 'Settings for comments and notifications',
            'form'        => [
                'default_comment_status' => [
                    'label' => 'Default article settings',
                ],
                'default_ping_status' => [
                    'label' => 'Default article settings',
                ],
                'comment_registration' => [
                    'label' => 'Before a comment appears',
                ],
                'comment_moderation' => [
                    'label' => 'Before a comment appears',
                ],
                'comment_whitelist' => [
                    'label' => 'Comment author must have a previously approved comment',
                ],
                'comment_blacklist' => [
                    'label' => 'Comment author must have a previously approved comment',
                ],
                'close_comments_for_old_posts' => [
                    'label' => 'Automatically close comments on articles older than',
                ],
                'email_notifications' => [
                    'label' => 'Email me whenever',
                ],
            ],
        ],
        'media' => [
            'title'       => 'Media',
            'description' => 'Settings for images, videos, and other media',
            'form'        => [],

        ],

        'permalinks' => [
            'title'       => 'Permalinks',
            'description' => 'Franken CMS lets you fully customize your URL structure. Create permalinks and archives that not only look great and work well but are also built for the future. Here are some examples to help you get started.',
            'form'        => [
                'placeholder' => [
                    'label'   => 'Custom Structure',
                    'content' => 'Select your site’s permalink structure. Including the <code>%postname%</code> tag makes URLs easy to read and can boost your search rankings.',
                ],
                'permalink_structure' => [
                    'label' => ' Permalink structure',
                ],
                'optional_placeholder' => [
                    'label'   => '',
                    'content' => 'Optionally, you can define custom structures for your category and tag URLs here. For example, setting “topics” as your category base would create links like <code>http://frankencms.test/topics/uncategorized/</code>. If left blank, the default settings will be applied.',
                ],
                'category_base' => [
                    'label' => 'Category base',
                ],
                'tag_base' => [
                    'label' => 'Tag base',
                ],

            ],

        ],

        'privacy' => [
            'title'       => 'Privacy',
            'description' => 'Settings for privacy and data protection',
            'form'        => [],

        ],

    ],

    'fields' => [
        'permalink_action_ok'      => 'OK',
        'permalink_action_edit'    => 'Edit permalink',
        'permalink_status_changed' => 'Permalink has been changed',
        'permalink_action_reset'   => 'Reset permalink',
        'permalink_action_cancel'  => 'Cancel',
    ],

];
