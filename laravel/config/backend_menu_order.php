<?php

// Stable sidebar groups. Changes are ordered within their existing parent.
return [
    [
        'label' => 'Modules and Extension',
        'children' => [],
        'id' => 'modules_and_extension',
        'path' => '/admin/modules-extensions',
    ],
    [
        'label' => 'Dashboard',
        'children' => [],
        'id' => 'dashboard',
        'path' => '/admin',
    ],
    [
        'label' => 'Analytics',
        'children' => [],
        'id' => 'analytics',
        'path' => '/admin/analytics',
    ],
    [
        'label' => 'Performance Logs',
        'children' => [],
        'id' => 'performance_logs',
        'path' => '/admin/performance-logs',
    ],
    [
        'label' => 'Products',
        'children' => [
            ['label'=>'Size Guide','children'=>[],'id'=>'products__size_guide','path'=>'/admin/website/size-guide'],
            [
                'label' => 'Add-ons / মোজা', 'children'=>[], 'id'=>'products__addons', 'path'=>'/admin/product-addons'],
            [
                'label' => 'Add New Product',
                'children' => [],
                'id' => 'products__add_new_product',
                'path' => '/admin/products/create',
            ],
            [
                'label' => 'Products',
                'children' => [],
                'id' => 'products__products',
                'path' => '/admin/products/admin',
            ],
            [
                'label' => 'Digital Products',
                'children' => [],
                'id' => 'products__digital_products',
                'path' => '/digitalproducts',
            ],
            [
                'label' => 'Bulk Import',
                'children' => [],
                'id' => 'products__bulk_import',
                'path' => '/product-bulk-upload/index',
            ],
            [
                'label' => 'Bulk Export',
                'children' => [],
                'id' => 'products__bulk_export',
                'path' => '/product-bulk-export',
            ],
            [
                'label' => 'Category',
                'children' => [],
                'id' => 'products__category',
                'path' => '/admin/categories',
            ],
            [
                'label' => 'Brand',
                'children' => [],
                'id' => 'products__brand',
                'path' => '/admin/brands',
            ],
            [
                'label' => 'Attribute',
                'children' => [],
                'id' => 'products__attribute',
                'path' => '/admin/attributes',
            ],
            [
                'label' => 'Colors',
                'children' => [],
                'id' => 'products__colors',
                'path' => '/admin/colors',
            ],
            [
                'label' => 'Product Reviews',
                'children' => [],
                'id' => 'products__product_reviews',
                'path' => '/admin/reviews',
            ],
        ],
        'id' => 'products',
        'path' => '#',
    ],
    [
        'label' => 'Sales',
        'children' => [
            [
                'label' => 'Orders',
                'children' => [],
                'id' => 'sales__orders',
                'path' => '/admin/all_orders',
            ],
            [
                'label' => 'Incomplete Orders',
                'children' => [],
                'id' => 'sales__incomplete_orders',
                'path' => '/admin/incomplete-orders',
            ],
            [
                'label' => 'Inhouse orders',
                'children' => [],
                'id' => 'sales__inhouse_orders',
                'path' => '/admin/inhouse-orders',
            ],
        ],
        'id' => 'sales',
        'path' => '#',
    ],
    [
        'label' => 'Customers',
        'children' => [
            [
                'label' => 'Customer list',
                'children' => [],
                'id' => 'customers__customer_list',
                'path' => '/admin/customers',
            ],
            [
                'label' => 'Returning Customers',
                'children' => [],
                'id' => 'customers__returning_customers',
                'path' => '/admin/returning-customers',
            ],
        ],
        'id' => 'customers',
        'path' => '#',
    ],
    [
        'label' => 'Media Center',
        'children' => [],
        'id' => 'media_center',
        'path' => '/admin/media-center',
    ],
    [
        'label' => 'Reports',
        'children' => [
            [
                'label' => 'Product Sales',
                'children' => [],
                'id' => 'reports__product_sales',
                'path' => '/admin/in_house_sale_report',
            ],
            [
                'label' => 'Products Stock',
                'children' => [],
                'id' => 'reports__products_stock',
                'path' => '/admin/stock_report',
            ],
            [
                'label' => 'Products wishlist',
                'children' => [],
                'id' => 'reports__products_wishlist',
                'path' => '/admin/wish_report',
            ],
            [
                'label' => 'User Searches',
                'children' => [],
                'id' => 'reports__user_searches',
                'path' => '/admin/user_search_report',
            ],
            [
                'label' => 'Wallet Recharge History',
                'children' => [],
                'id' => 'reports__wallet_recharge_history',
                'path' => '/admin/wallet-history',
            ],
        ],
        'id' => 'reports',
        'path' => '#',
    ],
    [
        'label' => 'Blog System',
        'children' => [
            [
                'label' => 'All Posts',
                'children' => [],
                'id' => 'blog_system__all_posts',
                'path' => '/admin/blog',
            ],
            [
                'label' => 'Categories',
                'children' => [],
                'id' => 'blog_system__categories',
                'path' => '/admin/blog-category',
            ],
        ],
        'id' => 'blog_system',
        'path' => '#',
    ],
    [
        'label' => 'Integrations',
        'children' => [
            [
                'label' => 'Steadfast Accounts',
                'children' => [
                    [
                        'label' => 'Manage / Add account',
                        'children' => [],
                        'id' => 'integrations__steadfast_accounts__manage_add_account',
                        'path' => '/admin/steadfast-accounts',
                    ],
                    [
                        'label' => 'Main account',
                        'children' => [],
                        'id' => 'integrations__steadfast_accounts__main_account',
                        'path' => '/admin/steadfast-accounts/legacy',
                    ],
                ],
                'id' => 'integrations__steadfast_accounts',
                'path' => '#',
            ],
            [
                'label' => 'BD Courier · Success report',
                'children' => [],
                'id' => 'integrations__bd_courier_success_report',
                'path' => '/admin/courier-success',
            ],
            [
                'label' => 'Meta Pixel & CAPI',
                'children' => [],
                'id' => 'integrations__meta_pixel_capi',
                'path' => '/admin/platform/meta',
            ],
            [
                'label' => 'Tracking integrations',
                'children' => [],
                'id' => 'integrations__tracking_integrations',
                'path' => '/admin/platform/tracking',
            ],
            [
                'label' => 'WhatsApp API settings',
                'children' => [],
                'id' => 'integrations__whatsapp_api_settings',
                'path' => '/admin/platform/whatsapp',
            ],
            [
                'label' => 'SMTP Settings',
                'children' => [],
                'id' => 'integrations__smtp_settings',
                'path' => '/admin/smtp-settings',
            ],
        ],
        'id' => 'integrations',
        'path' => '#',
    ],
    [
        'label' => 'Marketing',
        'children' => [
            ['label'=>'Homepage Banners','children'=>[],'id'=>'marketing__homepage_banners','path'=>'/admin/marketing/homepage-banners'],
            ['label'=>'Homepage Product Sections','children'=>[],'id'=>'marketing__homepage_sections','path'=>'/admin/marketing/homepage-sections'],
            [
                'label' => 'Flash deals',
                'children' => [],
                'id' => 'marketing__flash_deals',
                'path' => '/admin/flash_deals',
            ],
        ],
        'id' => 'marketing',
        'path' => '#',
    ],
    [
        'label' => 'Website Setup',
        'children' => [
            
            [
                'label' => 'Header',
                'children' => [],
                'id' => 'website_setup__header',
                'path' => '/admin/website/header',
            ],
            [
                'label' => 'Footer',
                'children' => [],
                'id' => 'website_setup__footer',
                'path' => '/admin/website/footer',
            ],
            [
                'label' => 'Pages',
                'children' => [],
                'id' => 'website_setup__pages',
                'path' => '/admin/website/pages',
            ],
            [
                'label' => 'Layout & Appearance',
                'children' => [],
                'id' => 'website_setup__layout_appearance',
                'path' => '/admin/platform/appearance',
            ],
        ],
        'id' => 'website_setup',
        'path' => '#',
    ],
    [
        'label' => 'Image Tools',
        'children' => [
            [
                'label' => 'Image Optimize',
                'children' => [],
                'id' => 'image_tools__image_optimize',
                'path' => '/admin/platform/images',
            ],
            [
                'label' => 'Image Resizer',
                'children' => [
                    [
                        'label' => 'Resize · Single / Bulk',
                        'children' => [],
                        'id' => 'image_tools__image_resizer__resize_single_bulk',
                        'path' => '/admin/platform/images/resizer',
                    ],
                    [
                        'label' => 'Convert 450×600 / 3:4',
                        'children' => [],
                        'id' => 'image_tools__image_resizer__convert_450_600_3_4',
                        'path' => '/admin/platform/images/square',
                    ],
                ],
                'id' => 'image_tools__image_resizer',
                'path' => '#',
            ],
            [
                'label' => 'Image Enhancer',
                'children' => [],
                'id' => 'image_tools__image_enhancer',
                'path' => '/admin/platform/images/enhancer',
            ],
        ],
        'id' => 'image_tools',
        'path' => '#',
    ],
    [
        'label' => 'Cleaner',
        'children' => [
            [
                'label' => 'Cache Management',
                'children' => [],
                'id' => 'cleaner__cache_management',
                'path' => '/admin/platform/cache',
            ],
            [
                'label' => 'Unused Photo / Image Cleaner',
                'children' => [],
                'id' => 'cleaner__unused_photo_image_cleaner',
                'path' => '/admin/store-operations/image-cleaner',
            ],
            [
                'label' => 'Old Image / Copy Cleanup',
                'children' => [],
                'id' => 'cleaner__old_image_copy_cleanup',
                'path' => '/admin/platform/image-storage',
            ],
            [
                'label' => 'Product Cleaner',
                'children' => [],
                'id' => 'cleaner__product_cleaner',
                'path' => '/admin/product-cleaner',
            ],
        ],
        'id' => 'cleaner',
        'path' => '#',
    ],
    [
        'label' => 'Customer Protection',
        'children' => [],
        'id' => 'customer_protection',
        'path' => '/admin/store-operations/protection',
    ],
    [
        'label' => 'Settings',
        'children' => [
            [
                'label' => 'General Settings',
                'children' => [],
                'id' => 'settings__general_settings',
                'path' => '/admin/general-setting',
            ],
            [
                'label' => 'Languages',
                'children' => [],
                'id' => 'settings__languages',
                'path' => '/admin/languages',
            ],
            [
                'label' => 'Currency',
                'children' => [],
                'id' => 'settings__currency',
                'path' => '/admin/currency',
            ],
            [
                'label' => 'Shipping',
                'children' => [
                    [
                        'label' => 'Shipping Configuration',
                        'children' => [],
                        'id' => 'settings__shipping__shipping_configuration',
                        'path' => '/admin/shipping_configuration',
                    ],
                    [
                        'label' => 'Shipping Countries',
                        'children' => [],
                        'id' => 'settings__shipping__shipping_countries',
                        'path' => '/admin/countries',
                    ],
                    [
                        'label' => 'Shipping States',
                        'children' => [],
                        'id' => 'settings__shipping__shipping_states',
                        'path' => '/admin/states',
                    ],
                    [
                        'label' => 'Shipping Cities',
                        'children' => [],
                        'id' => 'settings__shipping__shipping_cities',
                        'path' => '/admin/cities',
                    ],
                ],
                'id' => 'settings__shipping',
                'path' => 'void(0);',
            ],
            [
                'label' => 'Menu Order',
                'children' => [],
                'id' => 'settings__menu_order',
                'path' => '/admin/settings/menu-order',
            ],
        ],
        'id' => 'settings',
        'path' => '#',
    ],
    [
        'label' => 'Staffs',
        'children' => [
            [
                'label' => 'All Admins / সকল অ্যাডমিন',
                'children' => [],
                'id' => 'staffs__all_admins',
                'path' => '/admin/staffs/admins',
            ],
            [
                'label' => 'All staffs',
                'children' => [],
                'id' => 'staffs__all_staffs',
                'path' => '/admin/staffs',
            ],
            [
                'label' => 'Staff permissions',
                'children' => [],
                'id' => 'staffs__staff_permissions',
                'path' => '/admin/roles',
            ],
        ],
        'id' => 'staffs',
        'path' => '#',
    ],
    [
        'label' => 'Update, Backup & Restore',
        'children' => [
            [
                'label' => 'Updates & Rollback',
                'children' => [],
                'id' => 'update_backup_restore__updates_rollback',
                'path' => '/admin/platform/releases',
            ],
            [
                'label' => 'Website Backup & Restore',
                'children' => [],
                'id' => 'update_backup_restore__website_backup_restore',
                'path' => '/admin/website-backups',
            ],
        ],
        'id' => 'update_backup_restore',
        'path' => '#',
    ],
];
