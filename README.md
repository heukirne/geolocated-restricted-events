# geolocated-restricted-events
Event scheduled based on geolocation restrictions

- Config App at client_secret.json 
```
mv client_secret.dev.json client_secret.json
```

- Then generate Calendar Credentials:
```
curl -O https://getcomposer.org/composer.phar
php -f composer.phar install
php generate_credentials.php 
```

- Run server:
```
php -S localhost:8000
```