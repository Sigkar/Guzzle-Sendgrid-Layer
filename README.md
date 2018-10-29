# Guzzle-Sendgrid-Layer

Creates the ability to quickly add a user to the SendGrid V3 API via their email (Or whatever, edit the file as you like)

Uses Guzzle/Http as a dependency, you can get that via:
```
php composer.phar require guzzlehttp/guzzle
```

# Usage:

Add your Sendgrid API Key to your .env file, and you can also add your Sendgrid_List_Title if you wish. One of the functions will go through and find the list name you want to use.

After you have added that information, simply add this code as a controller to any action you recieve information through, pass the email/other information you need into it, and youre done!
