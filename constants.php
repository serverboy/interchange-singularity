<?php

// Settings for Interchange, the magic PHP framework!

// Uncomment this line to disable the session engine.
// define("NOSESSION", true);

// If a URL requests a directory without including a trailing slash, should the request be redirected?
define("REDIRECT_TRAILING_SLASH", false);

// If a URL requests a directory without including a trailing slash, should the request be processed anyway?
// Note: If set to true, the app will appear to work as if the slash was present.
define("HANDLE_TRAILING_SLASH", false);

// If a client requests a favicon file and one is not present, it is often more efficient to serve a default
// favicon so that the client does not poll again in the future. This can save non-insignificant amounts of
// bandwidth on busy websites. Enabling this option will serve a generic favicon file should one be absent
// from an endpoint. See the README file for license information on the favicon.
define("SERVE_DEFAULT_FAVICON", true);

// This should be changed to a random string. Random string generator:
// http://www.random.org/strings/?num=1&len=20&digits=on&upperalpha=on&unique=on
define('SECRET', 'THISISASECRET');
define('SUPER_SECRET', hash('sha256', SECRET));

// This is the chunk size that will be sent to the client. It should attempt to somewhat closely
// match the packet size for the server.
define('PACKET_SIZE', 4096);


// KEY VALUE PAIR SETTINGS

define('IXG_KV_STORAGE', 'memcached'); # (memcached|raw) - Key-value helper to load for storage.
define('IXG_KV_SESSIONS', true); # Set this to false to use PHP's default session handler.
define('IXG_KV_SESSIONS_TIMEOUT', 3600 * 24); # Sets the amount of time before the user's session expires.
define('IXG_KV_URL_CACHE', true); # Helps improve speed by caching frequently used URL schemes.

# NOTE: It is highly advised that if IXG_KV_STORAGE is set to "raw",
# IXG_KV_SESSIONS is set to false. The raw storage will not automatically
# flush expired sessions.

define('IXG_MEMCACHED', 'localhost'); # Hostname for the Memcached server
define('IXG_MEMCACHED_PORT', 11211); # The port for the Memcached server
define('IXG_MEMCACHED_TYPE', 'MEMCACHED'); # (MEMCACHED|MEMCACHE) - Which PECL extension should be accessed

define('IXG_RAW', '../keyval.json'); # File path for raw key-value storage.