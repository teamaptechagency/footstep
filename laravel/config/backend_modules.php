<?php

// Backend visibility controls only; existing permissions and public routes are independent.
return [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => 'la-home',
        'features' => [
            'overview' => [
                'label' => 'Dashboard overview',
                'routes' => ['admin.dashboard'],
            ],
        ],
    ],
    'analytics' => [
        'label' => 'Analytics',
        'icon' => 'la-chart-line',
        'features' => [
            'reports' => [
                'label' => 'Visitor, product & order analytics',
                'routes' => ['admin.analytics*'],
            ],
            'outcomes' => [
                'label' => 'Order success / cancel pie (Dashboard + Analytics)',
                'routes' => [],
            ],
        ],
    ],
    'performance' => [
        'label' => 'Performance Logs',
        'icon' => 'la-tachometer-alt',
        'features' => [
            'logs' => [
                'label' => 'Performance logs & diagnostics',
                'routes' => ['admin.performance-*', 'admin.performance-logs', 'admin.performance-settings'],
            ],
        ],
    ],
    'products' => [
        'label' => 'Products',
        'icon' => 'la-box',
        'features' => [
            'size_guide'=>['label'=>'Size Guide','routes'=>['size-guide.*']],
            'addons' => ['label'=>'Product add-ons', 'routes'=>['product-addons.*']],
            'create' => [
                'label' => 'Add New Product',
                'routes' => ['products.create', 'products.store'],
            ],
            'manage' => [
                'label' => 'Products / edit / stock',
                'routes' => ['bulk-product-*', 'bulk-product-delete', 'products.admin', 'products.admin.edit', 'products.all', 'products.approved', 'products.destroy', 'products.duplicate', 'products.featured', 'products.get_brands_by_category', 'products.get_products_by_brand', 'products.get_products_by_subcategory', 'products.published', 'products.seller', 'products.seller.edit', 'products.todays_deal', 'products.update'],
            ],
            'digital' => [
                'label' => 'Digital Products',
                'routes' => ['digitalproducts.*'],
            ],
            'import' => [
                'label' => 'Bulk Import',
                'routes' => ['bulk_product_upload', 'import_vendor_product.download', 'pdf.download_brand', 'pdf.download_category', 'pdf.download_seller', 'product_bulk_upload', 'product_bulk_upload.*', 'product_csv.download'],
            ],
            'export' => [
                'label' => 'Bulk Export',
                'routes' => ['product_bulk_export.*'],
            ],
            'categories' => [
                'label' => 'Category',
                'routes' => ['categories.*', 'categories.create', 'categories.destroy', 'categories.edit', 'categories.featured', 'categories.index', 'categories.show', 'categories.store', 'categories.update'],
            ],
            'brands' => [
                'label' => 'Brand',
                'routes' => ['brands.*', 'brands.create', 'brands.destroy', 'brands.edit', 'brands.index', 'brands.show', 'brands.store', 'brands.update'],
            ],
            'attributes' => [
                'label' => 'Attribute / sizes',
                'routes' => ['*attribute-value', 'attributes.*', 'attributes.create', 'attributes.destroy', 'attributes.edit', 'attributes.index', 'attributes.show', 'attributes.store', 'attributes.update', 'destroy-attribute-value', 'edit-attribute-value', 'store-attribute-value', 'update-attribute-value'],
            ],
            'colors' => [
                'label' => 'Colors',
                'routes' => ['colors', 'colors.*', 'colors.destroy', 'colors.edit', 'colors.store', 'colors.update'],
            ],
            'reviews' => [
                'label' => 'Product Reviews',
                'routes' => ['platform.reviews.*', 'platform.reviews.index', 'reviews.manual.*', 'reviews.manual.create', 'reviews.manual.delete', 'reviews.manual.edit', 'reviews.manual.samples', 'reviews.manual.store', 'reviews.manual.update', 'reviews.published'],
            ],
        ],
    ],
    'sales' => [
        'label' => 'Sales',
        'icon' => 'la-shopping-bag',
        'features' => [
            'orders' => [
                'label' => 'All Orders',
                'routes' => ['all_orders.*'],
            ],
            'incomplete' => [
                'label' => 'Incomplete Orders',
                'routes' => ['admin.incomplete-orders*'],
            ],
            'inhouse' => [
                'label' => 'Inhouse Orders',
                'routes' => ['inhouse_orders.*'],
            ],
            'manage' => [
                'label' => 'Order details, invoice & status changes',
                'routes' => ['bulk-order-*', 'invoice.download', 'orders.*', 'store-ops.cancel', 'store-ops.edit', 'store-ops.invoice'],
            ],
        ],
    ],
    'customers' => [
        'label' => 'Customers',
        'icon' => 'la-user-friends',
        'features' => [
            'list' => [
                'label' => 'Customer list / management',
                'routes' => ['bulk-customer-delete', 'bulk_customer_upload', 'bulk_user_upload', 'customer_bulk_upload.*', 'customer_bulk_upload.index', 'customers.*', 'customers.ban', 'customers.create', 'customers.destroy', 'customers.edit', 'customers.index', 'customers.login', 'customers.show', 'customers.store', 'customers.update', 'pdf.download_user'],
            ],
            'returning' => [
                'label' => 'Returning Customers',
                'routes' => ['admin.returning-customers*'],
            ],
        ],
    ],
    'media' => [
        'label' => 'Media Center',
        'icon' => 'la-photo-video',
        'features' => [
            'library' => [
                'label' => 'Media library / uploads',
                'routes' => ['admin.media-center', 'admin.media-center*', 'admin.media-center.action', 'aiz-upload.*', 'uploaded-files.*', 'uploaded-files.create', 'uploaded-files.destroy', 'uploaded-files.edit', 'uploaded-files.index', 'uploaded-files.info', 'uploaded-files.show', 'uploaded-files.store', 'uploaded-files.update'],
            ],
        ],
    ],
    'reports' => [
        'label' => 'Reports',
        'icon' => 'la-file-alt',
        'features' => [
            'sales' => [
                'label' => 'Product Sales',
                'routes' => ['in_house_sale_report.*', 'seller_sale_report.*'],
            ],
            'stock' => [
                'label' => 'Products Stock',
                'routes' => ['stock_report.*'],
            ],
            'wishlist' => [
                'label' => 'Products wishlist',
                'routes' => ['wish_report.*'],
            ],
            'searches' => [
                'label' => 'User Searches',
                'routes' => ['user_search_report.*'],
            ],
            'wallet' => [
                'label' => 'Wallet Recharge History',
                'routes' => ['wallet-history.*'],
            ],
        ],
    ],
    'blog' => [
        'label' => 'Blog System',
        'icon' => 'la-blog',
        'features' => [
            'posts' => [
                'label' => 'All Posts',
                'routes' => ['blog.*', 'blog.change-status', 'blog.create', 'blog.destroy', 'blog.edit', 'blog.index', 'blog.show', 'blog.store', 'blog.update'],
            ],
            'categories' => [
                'label' => 'Categories',
                'routes' => ['blog-category.*', 'blog-category.create', 'blog-category.destroy', 'blog-category.edit', 'blog-category.index', 'blog-category.show', 'blog-category.store', 'blog-category.update'],
            ],
        ],
    ],
    'integrations' => [
        'label' => 'Integrations',
        'icon' => 'la-plug',
        'features' => [
            'steadfast' => [
                'label' => 'Steadfast accounts, test / sync / booking',
                'routes' => ['integration-health.steadfast*', 'steadfast-accounts.*', 'steadfast-accounts.health', 'steadfast-accounts.index', 'steadfast-accounts.save', 'steadfast-accounts.send', 'steadfast-accounts.show', 'steadfast-accounts.sync', 'steadfast-accounts.test', 'store-ops.save', 'store-ops.send', 'store-ops.settings', 'store-ops.sync'],
            ],
            'bdcourier' => [
                'label' => 'BD Courier success / fraud report',
                'routes' => ['courier-success.*', 'courier-success.check', 'courier-success.save', 'courier-success.settings'],
            ],
            'meta' => [
                'label' => 'Meta Pixel & CAPI settings',
                'routes' => ['integration-health.meta*', 'platform.meta', 'platform.meta*', 'platform.meta.retry', 'platform.meta.save', 'platform.meta.test'],
            ],
            'tracking' => [
                'label' => 'Tracking integrations',
                'routes' => ['platform.tracking*'],
            ],
            'sms' => ['label' => 'SMS.net.bd order notifications', 'routes' => ['platform.sms*']],
            'whatsapp' => [
                'label' => 'WhatsApp API settings',
                'routes' => ['platform.whatsapp*'],
            ],
            'smtp' => [
                'label' => 'SMTP Settings',
                'routes' => ['smtp_settings.*', 'test.smtp'],
            ],
        ],
    ],
    'marketing' => [
        'label' => 'Marketing',
        'icon' => 'la-bullhorn',
        'features' => [
            'homepage_banners'=>['label'=>'Homepage Banners','routes'=>['homepage-banners.*']],
            'homepage_sections'=>['label'=>'Homepage Product Sections','routes'=>['homepage-sections.*']],
            'flash' => [
                'label' => 'Flash deals',
                'routes' => ['flash_deals.*', 'flash_deals.create', 'flash_deals.destroy', 'flash_deals.edit', 'flash_deals.index', 'flash_deals.product_discount', 'flash_deals.product_discount_edit', 'flash_deals.show', 'flash_deals.store', 'flash_deals.update', 'flash_deals.update_featured', 'flash_deals.update_status'],
            ],
        ],
    ],
    'website' => [
        'label' => 'Website Setup',
        'icon' => 'la-desktop',
        'features' => [
            
            'header' => [
                'label' => 'Header',
                'routes' => ['website.header'],
            ],
            'footer' => [
                'label' => 'Footer',
                'routes' => ['website.footer'],
            ],
            'pages' => [
                'label' => 'Pages',
                'routes' => ['custom-pages.*', 'website.pages'],
            ],
            'appearance' => [
                'label' => 'Layout & Appearance',
                'routes' => ['platform.appearance', 'platform.appearance*', 'platform.appearance.save', 'platform.backend-theme.*', 'platform.backend-theme.save', 'platform.developer-credit.*', 'platform.developer-credit.save', 'website.appearance'],
            ],
        ],
    ],
    'images' => [
        'label' => 'Image Tools',
        'icon' => 'la-images',
        'features' => [
            'optimize' => [
                'label' => 'Image Optimize',
                'routes' => ['platform.images', 'platform.images.batch', 'platform.images.optimize', 'platform.images.settings'],
            ],
            'resize' => [
                'label' => 'Resize · Single / Bulk',
                'routes' => ['platform.images.resize', 'platform.images.resizer'],
            ],
            'portrait' => [
                'label' => 'Convert 450×600 / 3:4',
                'routes' => ['platform.images.convert', 'platform.images.square'],
            ],
            'enhance' => [
                'label' => 'Image Enhancer',
                'routes' => ['platform.images.enhance', 'platform.images.enhancer'],
            ],
            'history' => [
                'label' => 'Apply saved image / restore',
                'routes' => ['platform.images.choose'],
            ],
        ],
    ],
    'cleaner' => [
        'label' => 'Cleaner',
        'icon' => 'la-broom',
        'features' => [
            'cache' => [
                'label' => 'Cache Management / Clear Cache',
                'routes' => ['cache.clear', 'platform.cache', 'platform.cache*', 'platform.cache.device', 'platform.cache.network', 'platform.cache.server', 'platform.cache.settings'],
            ],
            'unused' => [
                'label' => 'Unused Photo / Image Cleaner',
                'routes' => ['store-ops.clean', 'store-ops.cleaner', 'store-ops.image-restore', 'store-ops.scan'],
            ],
            'copies' => [
                'label' => 'Old Image / Copy Cleanup',
                'routes' => ['image-storage.*', 'image-storage.delete', 'image-storage.index', 'image-storage.scan', 'image-storage.step', 'image-storage.versions'],
            ],
            'products' => [
                'label' => 'Product Cleaner',
                'routes' => ['cleaner.products', 'cleaner.products*', 'cleaner.products.action'],
            ],
        ],
    ],
    'protection' => [
        'label' => 'Customer Protection',
        'icon' => 'la-user-shield',
        'features' => [
            'manage' => [
                'label' => 'IP / number block and unblock controls',
                'routes' => ['store-ops.ip', 'store-ops.protect', 'store-ops.protection*'],
            ],
        ],
    ],
    'settings' => [
        'label' => 'Settings',
        'icon' => 'la-cog',
        'features' => [
            'menu_order' => [
                'label' => 'Menu Order',
                'routes' => ['admin.menu-order.*'],
            ],
            'general' => [
                'label' => 'General Settings',
                'routes' => ['business_settings.update', 'general_setting.*'],
            ],
            'languages' => [
                'label' => 'Languages',
                'routes' => ['app-translations.export', 'app-translations.import', 'app-translations.show', 'app-translations.store', 'languages.*', 'languages.create', 'languages.destroy', 'languages.edit', 'languages.index', 'languages.key_value_store', 'languages.show', 'languages.store', 'languages.update', 'languages.update_rtl_status'],
            ],
            'currency' => [
                'label' => 'Currency',
                'routes' => ['currency.*', 'currency.create', 'currency.edit', 'currency.index', 'currency.store', 'currency.update', 'currency.update_status', 'your_currency.*', 'your_currency.update'],
            ],
            'shipping' => [
                'label' => 'Shipping Configuration',
                'routes' => ['shipping_configuration.*'],
            ],
            'countries' => [
                'label' => 'Shipping Countries',
                'routes' => ['countries.*', 'countries.create', 'countries.destroy', 'countries.edit', 'countries.index', 'countries.show', 'countries.status', 'countries.store', 'countries.update'],
            ],
            'states' => [
                'label' => 'Shipping States',
                'routes' => ['states.*', 'states.create', 'states.destroy', 'states.edit', 'states.index', 'states.show', 'states.status', 'states.store', 'states.update'],
            ],
            'cities' => [
                'label' => 'Shipping Cities',
                'routes' => ['cities.*', 'cities.create', 'cities.destroy', 'cities.edit', 'cities.index', 'cities.show', 'cities.status', 'cities.store', 'cities.update'],
            ],
        ],
    ],
    'staff' => [
        'label' => 'Staffs',
        'icon' => 'la-user-tie',
        'features' => [
            'admins' => [
                'label' => 'All Admins',
                'routes' => ['staffs.admins', 'staffs.admins*'],
            ],
            'members' => [
                'label' => 'All staffs',
                'routes' => ['staffs.*', 'staffs.create', 'staffs.destroy', 'staffs.edit', 'staffs.index', 'staffs.show', 'staffs.store', 'staffs.update'],
            ],
            'roles' => [
                'label' => 'Staff permissions',
                'routes' => ['roles.*', 'roles.create', 'roles.destroy', 'roles.edit', 'roles.index', 'roles.show', 'roles.store', 'roles.update'],
            ],
        ],
    ],
    'maintenance' => [
        'label' => 'Update, Backup & Restore',
        'icon' => 'la-shield-alt',
        'features' => [
            'updates' => [
                'label' => 'Updates & Rollback',
                'routes' => ['platform.releases', 'platform.releases*', 'platform.releases.install', 'platform.releases.rollback', 'platform.releases.upload', 'system_update', 'system_update.*', 'update', 'update.*'],
            ],
            'backups' => [
                'label' => 'Website Backup & Restore',
                'routes' => ['website-backup.*', 'website-backup.action', 'website-backup.create', 'website-backup.delete-failed', 'website-backup.diagnostics', 'website-backup.download', 'website-backup.index', 'website-backup.rollback-preview', 'website-backup.status', 'website-backup.stop', 'website-backup.storage', 'website-backup.storage.scan', 'website-backup.storage.step', 'website-backup.upload'],
            ],
        ],
    ],
    'notifications' => [
        'label' => 'Notifications',
        'icon' => 'la-bell',
        'features' => [
            'notifications' => [
                'label' => 'Admin notifications',
                'routes' => ['admin.notification-open', 'store-ops.notifications'],
            ],
        ],
    ],
];
