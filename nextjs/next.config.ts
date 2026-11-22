import type { NextConfig } from 'next';
const config: NextConfig = {
  distDir: process.env.FOOTSTEP_NEXT_DIST || '.next',
  poweredByHeader: false,
  serverExternalPackages: ['mmdb-lib'],
  async rewrites(){return [{source:'/cart',destination:'/regional-commerce/cart'},{source:'/brands',destination:'/regional-commerce/brands'},{source:'/brand/:slug',destination:'/regional-commerce/brand/:slug'},{source:'/category/:slug',destination:'/regional-commerce/category/:slug'},{source:'/users/login',destination:'/regional-commerce/users/login'},{source:'/users/registration',destination:'/regional-commerce/users/registration'},{source:'/users/register',destination:'/regional-commerce/users/register'},{source:'/password/reset',destination:'/regional-commerce/password/reset'},{source:'/password/reset/:token',destination:'/regional-commerce/password/reset/:token'},{source:'/checkout',destination:'/regional-commerce/checkout'},{source:'/purchase_history',destination:'/regional-commerce/purchase_history'},{source:'/purchase_history/:id',destination:'/regional-commerce/purchase_history/:id'},{source:'/wishlists',destination:'/regional-commerce/wishlists'},{source:'/dashboard',destination:'/regional-commerce/dashboard'},{source:'/track-your-order',destination:'/regional-commerce/track-your-order'},{source:'/order-track',destination:'/regional-commerce/order-track'},{source:'/returnpolicy',destination:'/regional-commerce/returnpolicy'},{source:'/page/:slug',destination:'/regional-commerce/page/:slug'}];},
  // Preserve the installed Next default crawler list, also block metadata for Googlebot.
  htmlLimitedBots: /Googlebot|[\w-]+-Google|Google-[\w-]+|Chrome-Lighthouse|Slurp|DuckDuckBot|baiduspider|yandex|sogou|bitlybot|tumblr|vkShare|quora link preview|redditbot|ia_archiver|Bingbot|BingPreview|applebot|facebookexternalhit|facebookcatalog|Twitterbot|LinkedInBot|Slackbot|Discordbot|WhatsApp|SkypeUriPreview|Yeti|googleweblight/i,
  async headers() {
    return [{ source: '/:path*', headers: [
      { key: 'X-Content-Type-Options', value: 'nosniff' },
      { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' }
    ] }];
  }
};
export default config;
