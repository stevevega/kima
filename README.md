# Kima PHP Framework

Kima is a PHP framework designed to provide a structured and efficient way to build web applications. It offers a range of components for common web development tasks, including database interaction, caching, localization, and more.

## Overview

Kima follows modern PHP practices and aims to be a flexible tool for developers. It includes support for different database systems, caching mechanisms, and provides utilities for handling HTTP requests, managing views, and performing common tasks like image manipulation and file uploads. The framework is structured into various modules, each catering to specific functionalities.

## Usage

1.  [Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
2.  Execute `composer create-project stevevega/kima-skeleton [DESTINATION_PATH]`
3.  Make your webserver point to `[DESTINATION_PATH]/public`

### Example Nginx Server Config
```nginx
server {
    listen 80;
    server_name [YOUR_DOMAIN];

    root [DESTINATION_PATH]/public;
    index index.html index.htm index.php;

    location / {
        # This is cool because no php is touched for static content
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        # Filter out arbitrary code execution
        location ~ \..*/.*\.php$ {return 404;}

        include fastcgi_params;
        fastcgi_param SERVER_NAME $http_host;
        fastcgi_pass unix:/tmp/php.socket;
    }
}
```

## Core Components

The Kima framework is composed of several key components found within the `library/Kima` directory:

* **App (Prime/App.php)**: The main entry point for applications using Kima. It handles the setup of the application environment, configuration, routing, and request lifecycle.
* **Action (Prime/Action.php)**: Implements the Front Controller design pattern. It's responsible for routing incoming requests to the appropriate controllers and methods.
* **Cache (Cache.php)**: Provides a factory for various caching mechanisms. Supported systems include:
    * APC (Cache/Apc.php)
    * File-based cache (Cache/File.php)
    * Memcached (Cache/Memcached.php)
    * Redis (Cache/Redis.php)
    * A NullObject cache for disabling caching. (Cache/NullObject.php)
    * An interface `ICache.php` defines the contract for cache adapters.
* **Config (Prime/Config.php)**: Handles application configuration, supporting global, module-specific, and custom environment configurations.
* **Controller (Prime/Controller.php)**: Base controller class that other controllers in the application should extend. It provides integration with the View component and helper methods.
* **Crypt (Crypt/)**: Offers cryptographic utilities:
    * `BCrypt.php`: For password hashing using BCrypt.
    * `GeoHash.php`: For encoding latitude and longitude.
    * `Nonce.php`: For generating nonce strings.
* **Database (Database.php)**: An abstract factory for database interaction. It supports:
    * MongoDB (Database/Mongo.php)
    * PDO for MySQL (Database/Pdo.php)
    * Interfaces `IDatabase.php` and `ITransaction.php` define contracts for database operations and transactions.
* **Error (Error.php)**: Handles user-triggered errors and logging within the application, integrating with DataDog for tracing.
* **Html (Html/CssToInline.php)**: Utility for converting CSS styles to inline styles within HTML documents, useful for email templates.
* **Http (Http/)**: Components for handling HTTP-related tasks:
    * `Redirector.php`: Manages HTTP redirects.
    * `Request.php`: Provides access to HTTP request data (GET, POST, COOKIE, SERVER, etc.).
    * `StatusCode.php`: Defines and retrieves messages for HTTP status codes.
* **Image (Image.php)**: A library for image manipulation, extending Imagick. It supports operations like creating thumbnails, cropping, and converting image formats, as well as fixing EXIF rotation.
* **L10n (L10n.php)**: Handles localization (l10n) by loading language strings from `.ini` files, supporting caching of translations.
* **Logger (Logger.php)**: A logging class that extends the base Model, allowing logs to be stored, typically in MongoDB.
* **Model (Model.php)**: An abstract base class for data models, providing an abstraction layer for database operations. It supports different database engines (MySQL, MongoDB) through adapters.
    * `IModel.php`: Interface for model adapters.
    * `Mongo.php` (Adapter, appears to be an interface or incomplete)
    * `Mysql.php` (Adapter for MySQL)
    * `ResultSet.php`: Class for storing query result sets, including total counts for pagination.
    * `TFilter.php`: A trait used by models (likely `Mysql.php`) for parsing query filter operators.
* **Procedure (Procedure.php)**: Abstract class for working with database stored procedures, primarily with MySQL.
* **Search (Search/Solr.php)**: Provides an interface for interacting with the Solr search engine.
* **Upload (Upload/)**: Handles file and image uploads.
    * `File.php`: Base class for file uploads, including validation for size and type.
    * `Image.php`: Extends `File.php` for image-specific uploads, including EXIF rotation fixing and format conversion.
* **Util (Util/)**: Contains various utility classes:
    * `Html.php`: Utilities for HTML manipulation, such as extracting text and removing tags.
    * `KInt.php`: Integer utility functions, like casting to integer.
    * `KString.php`: String utility functions, including camel case to underscore conversion, slug generation, and creating comma-separated lists.
* **View (View.php)**: Manages the template system. It supports loading views, setting variables, rendering templates, handling layouts, and including CSS/JavaScript. It also supports caching of template blocks and localization of strings within templates.

## Key Features

* **MVC-like Architecture**: Organizes code into Models, Views, and Controllers (Prime Components).
* **Routing**: Flexible routing mechanism to map URLs to controller actions (Prime/Action.php).
* **Database Abstraction**: Supports multiple database systems (MongoDB, MySQL) with a common interface (Database.php, Model.php).
* **Caching System**: Multiple caching backends (APC, File, Memcached, Redis) (Cache.php).
* **Templating Engine**: A simple yet powerful view system with support for layouts, blocks, and variable passing (View.php).
* **Localization (L10n)**: Support for internationalization through `.ini` language files (L10n.php).
* **Error Handling**: Centralized error management (Error.php).
* **HTTP Utilities**: Easy handling of HTTP requests, responses, and status codes (Http/*).
* **Security Utilities**: Includes tools for password hashing (BCrypt) and nonce generation (Crypt/*).
* **File and Image Handling**: Robust file and image uploading and manipulation capabilities (Upload/*, Image.php).
* **Search Integration**: Support for Solr search engine (Search/Solr.php).
* **Composer Ready**: Uses Composer for dependency management.
* **DataDog Tracing**: Integrated support for DataDog APM tracing.

## Dependencies

Kima PHP Framework requires the following:

* PHP >= 8.1
* `mongodb/mongodb`: ^1.15.0
* `datadog/dd-trace`: >=0.86.3

It also suggests or implicitly requires PHP extensions like `pdo_mysql`, `memcached`, `redis`, `apc`, `imagick`, and `solr` depending on the features used.
