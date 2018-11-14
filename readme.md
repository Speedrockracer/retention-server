# Retention assessment

The app is run in a docker container. To run make sure docker is installed and running.
From the command line execute the following commands:

### Setup
* git clone https://github.com/Speedrockracer/retention-assesment.git retention-assesment
* cd retention-assesment
* docker run --rm -v $(pwd):/app composer/composer install

### Test
* docker run --rm -v $(pwd):/app composer ./vendor/bin/phpunit --testdox --bootstrap vendor/autoload.php app

### Run
* docker-compose up
* visit http://localhost:8080 in your browser

The compose up command might take a while the first time.

Also the page load time might be a bit slow because of the filewatcher docker uses. Didn't have time to fix that.
