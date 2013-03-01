Welcome to Sharetronix Opensource
  ------------------------
  Sharetronix Opensource is a multimedia microblogging platform. It helps
  people in a community, company, or group to exchange short messages over
  the Web. Find more information in http://developer.sharetronix.com/
  -------------------------

License
  -------------------------
  Please check out the license.txt file. By installing Sharetronix, you
  agree to all the conditions of the license and also to the Sharetronix
  Terms of Use: http://developer.sharetronix.com/license
  -------------------------
  
Powered by Sharetronix link
  -------------------------
  Please check out the license.txt file. By installing Sharetronix, you
  agree to keep the Powered by Sharetronix backlink in your system. If you wish to   
  remove the link you need to purchase a link removal option. To do so please go to   
  http://developer.sharetronix.com/appstore/app/name/Powered%20By%20Sharetronix%20Link%20Removal/id/8
  
INSTALLATION
  -------------------------
  To install Sharetronix Opensource on your webserver, upload the contents
  of the "upload/" folder to the preferred location on your webserver
  (wherever you want to install Sharetronix) with your favorite FTP client.
  Open with your browser the "install" location in this folder and follow
  the steps in the installation wizard.
  -------------------------

UPGRADE
  -------------------------
  To upgrade Sharetronix Opensource from a previous version, just follow
  the Installation steps. Replace the old installation files with the
  contents of the "upload/" folder and run the installation wizard. But
  first - don't forget to backup your old installation (database and files)
  - it's important!
  -------------------------

System Requirements
  -------------------------
  - Apache Web Server
  - MySQL version 5.0 or higher
  - PHP version 5.2 or higher
  -------------------------

Official website
  -------------------------
  http://sharetronix.com
  http://sharetronixmicro.com
  http://developer.sharetronix.com/
  -------------------------

Important security setting
  --------------------------
  
If you want to increase the security level of attached images and files in your community, 
uncomment the row "php_flag engine off" in ./static/.htaccess and ./storage/.htaccess files

Mail send problems
  --------------------------
  
  If you have problems with the emails distribution and one of these two problems:
  - send emails with empty body
  - send emails with incomplete text in the body
 
  You can resolve this issue by changing a few items in the file ./system/helpers/func_main.php
  Go to line 126 and delete all lines that contain the phrase (DELETE_THIS_LINE):
  
  ----------------------------------------------
  /*  (DELETE_THIS_LINE) This is a fix for everybody with mail issues (2 types)
      (DELETE_THIS_LINE) 1. Your script send mails with blank body
      (DELETE_THIS_LINE) 2. Your script send mails with missing text in the mail body
               
      (DELETE_THIS_LINE) To activate the fix delete all the lines which contains (DELETE_THIS_LINE).
 
	do_send_mail($email, $subject, $message_txt, $from);
	return;
 
   (DELETE_THIS_LINE)*/
  ----------------------------------------------        
  
  Once you have deleted the lines you should have only the following code left:
 
  ----------------------------------------------
   do_send_mail($email, $subject, $message_txt, $from);
   return;
  ----------------------------------------------
 
  Note: please backup the file ./system/helpers/func_main.php before applying any changes


  -------------------------

Using Google reCaptcha

  -------------------------
  
  To activate the google reCaptcha on your sharetronix community follow the steps below:
  1. Go to https://www.google.com/recaptcha/admin/create 
  2. Create your private and public keys.
  3. Open the ./conf_main.php file
  4. Enter the value for the private key at $C->GOOGLE_CAPTCHA_PRIVATE_KEY 
  5. Enter the value for the public key at $C->GOOGLE_CAPTCHA_PUBLIC_KEY
  

  -------------------------
  
One click install
  
  -------------------------
  Softaculous 	- http://www.softaculous.com/softwares/microblogs/Sharetronix
  AMPPS		- http://www.ampps.com/apps/php/microblogs/Sharetronix
  
  
  -------------------------
  
FACEBOOK CONNECT

  -------------------------
  To activate Facebook Connect integration for your Sharetronix site, first
  you have to register a Facebook application and get its API key:
  1. Complete the Sharetronix installation/upgrade script
  2. Go to FB and join the Developers group: http://developers.facebook.com/
  3. Go to https://developers.facebook.com/apps and click on "Create new app" button
  4. Fill in only the "App Name" field with the name of your application and click on the "Continue" button.
  5. Enter the captcha code if captcha popup appear and click on the "Continue" button. 
  6. Fill the "App Domain", if your sharetronix community is hosted at http://www.site.com or http://app.site.com or http://site.com/app, your domain is site.com 
  7. Click on the "Website with Facebook Login" section and add you community url, eg. http://www.site.com,  http://app.site.com, http://site.com/app
  8. Click on the "Save changes" button
  9. Your App key/ID and App Secret are in your application description, copy both and paste in your ./system/conf_main.php file in  
  
  $C->FACEBOOK_API_KEY		= 'Your Key';
  $C->FACEBOOK_API_ID		= 'Your app id'; 
  $C->FACEBOOK_API_SECRET	= 'Your app secret';


  -------------------------
  
TWITTER CONNECT

  -------------------------
  To activate Twitter OAuth Login for your Sharetronix site, first you have
  to register a Twitter application and get its Consumer KEY and SECRET:
  1. Complete the Sharetronix installation/upgrade script
  2. Go to the Twitter New Application form: https://dev.twitter.com/apps and click on "Create New Application" button
  3. Fill App Name, Description and Website(eg. http://mysharetronixcommunity.com).
  4. Agree with "Developer Rules Of The Road", enter the captcha code and click on the "Create your Twitter application" button.
  5. Click on the "Settings" tab and in the "Application Type" section for access type choose "Read and Write" and click on "Update this Twitter's application" button
  6. Go to the "Details" tab and copy your application's "Consumer key"	and "Consumer secret" and paste them in your ./system/conf_main.php file in the 
  
  $C->TWITTER_CONSUMER_KEY	= '';
  $C->TWITTER_CONSUMER_SECRET	= '';
  
  7. You MUST also put a Callback URL in the SETTINGS tab in your twitter application. Set it to your community URL
  
  
  -------------------------
  
BIT.LY

  -------------------------
  To activate Bit.ly
  
  1. Create an account in Bit.ly 
  2. Go to Settings->Advanced
  3. Copy "Login" and "API Key" values 
  4. Paste them to 
  
  $C->BITLY_LOGIN	= '';
  $C->BITLY_API_KEY	= '';