# Kima

Kima PHP Framework

## Usage
1. [Install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
2. Execute "composer create-project stevevega/kima-skeleton [DESTINATION PATH]"
3. Make your webserver point to [DESTINATION PATH]/public

### Example nginx server config

```
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
