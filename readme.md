Moodle Authorize.net Plugin [enrol_authorizedotnet]
=======================
* Developed by: Team DualCube
* Copyright: (c) 2021 DualCube <admin@dualcube.com>
* License: [GNU GENERAL PUBLIC LICENSE](LICENSE)
* Contributors:  DualCube

Description
===========
This plugin helps admins and webmasters use Authorize.net as the payment gateway. Authorize.net is one of the popular and best secure payment gateways. This plugin has all the settings for development as well as for production usage. It is easy to install, setup and effective.

_Added features include:_
* Enhanced AVS Filter Compatible
* Simple checkout. 
* Full course name and site logo on checkout
* Ability to set custom stripe instance name for each course
* Admins and Webmasters, now, can create, manage, and keep track of all transactions directly in their Authorize.net dashboard.

Installation
============
1. Login to your moodle site as an “admin user” and follow the steps.
2. Upload the zip package from Site administration > Plugins > Install plugins. Choose Plugin type 'Enrolment method (enrol)'. Upload the ZIP package, check the acknowledgement and install.
3. Go to Enrolments > Manage enrol plugins > Enable 'Authorize.net' from list
4. Click 'Settings' which will lead to the settings page of the plugin
5. Provide merchant credentials for Authorize.net . Note that, you will get all the details from your merchant account.  Now select the checkbox as per requirement. Save the settings.
   * __Note:__ When upgrading from a version of the plugin before v2.6.5, be sure to adjust the `checkproductionmode` ("Enable test Mode") setting as needed. This checkbox has changed its wording and meaning. It is now _unchecked_ for production accounts and _checked_ for sandbox accounts.
6. Enable Web Service: Administration > Development Section > Advanced Features option. scroll down and tick the Web Service option, and save.
7. Manage Protocol: Site Administration > Server tab > Web Services > Manage Protocols. Click on the eye icon on the REST protocol, and save.
8. Select any course from course listing page.
9. Go to Course administration > Participants > Enrolment methods > Add method 'Authorize.net' from the dropdown. Set 'Custom instance name', 'Enrol cost', 'Currency' etc and add the method.
10. This completes all the steps from the administrator end. Now registered users can login to the Moodle site and view the course after a successful payment.

[Note: If you missed step no. 6 & 7 - it will give error-403 on payment page ]



Requirements
------------
* Moodle 4.1+
* Authorize.net account


Authorize.net account
=====================
1. Create account at authorize.net or developer.authorize.net https://www.authorize.net/sign-up/become-a-partner/become-a-reseller/reseller-application.html or https://developer.authorize.net/hello_world/sandbox.html. 
2. Log in to https://partner.authorize.net/widget/widget/RINT/SPA or https://sandbox.authorize.net/


To add a Default Relay Response URL:
-----------------------------------
1. Log into the Merchant Interface at https://account.authorize.net/ or Sandbox Interface https://sandbox.authorize.net/.
2. Click Account from the main toolbar.
2. Click Response/Receipt URLs under Transaction Format Settings.
3. Click Edit next to Default Relay Response URL. The Relay Response page appears.
4. In the URL text field, enter the URL where the payment gateway should send the transaction response. This URL must start with either "http://" or "https://". Parameterized URLs are not permitted.
5. Click Submit. A confirmation message indicates that the URL has been added.



To add a URL to the list of authorized Response or Receipt URLs:
---------------------------------------------------------------
1. Log into the Merchant Interface at https://account.authorize.net/ or Sandbox Interface https://sandbox.authorize.net/.
2. Click Account from the main toolbar.
2. Click Response/Receipt URLs under Transaction Format Settings.
3. Click Add URL.
4. Enter the new URL. This URL must start with either "http://" or "https://".
5. Click Submit.

To generate signature key:
--------------------------
1. Log into the Merchant Interface at https://account.authorize.net/ or Sandbox Interface https://sandbox.authorize.net/.
2. Click Account from the main toolbar.
3. Click API Credential & key option.
4. Select your Secret Answer Key(For all test account the answer will be Simon) and choose new signature key option.You can see an option to disable the old signature key(If you want to disable the old one you can do it by checking the box).
5. Then press submit to generate your signature key.

Useful links
============
* Moodle Forum: [https://moodle.org/course](https://moodle.org/course)
* Moodle Plugins Directory:  [https://moodle.org/plugins](https://moodle.org/plugins)
* Authorize.net API: [https://developer.authorize.net/api/reference/index.html](https://developer.authorize.net/api/reference/index.html)
* DualCube Contributions: [https://moodle.org/plugins/browse.php?list=contributor&id=1832609](https://moodle.org/plugins/browse.php?list=contributor&id=1832609)


Release history
===============
* **v1.0:** 2016-05-05