# Getting Started with Backend of `Laravel` and `React` App


> This  directory contains the Laravel project


## Run Laravel Project

In the this directory, 

1. Run `composer install`.

2. Rename the `.env.example` file to `.env`.

3. Copy the `DB_DATABASE` value and make a Database with that name.

4. Generate Laravel Application Key `php artisan key:generate`

5. Run Database Migration and run seeders `php artisan migrate --seed` . 
    
    * One admin type user and One user type user will be created.

    * Admin Credentials

        * email : admin@admin.com
        * password : 123

    * User Credentials
    
        * email : user@user.com
        * password : 123

6. Run `php artisan serve`
