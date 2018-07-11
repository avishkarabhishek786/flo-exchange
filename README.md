# License

Copyright (c) 2017-2018 GitHub Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software
is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.



# flo-exchange

This is a code for setting up an online exchange or trading platform.  

The script contains basic functionalities of buying, selling and market order of, in fact, any article that can be traded online
(Florincoin tokens in our case). The system also contains a basic Facebook authentication system to store users basic information.
The website has basic display of users, transactions and other important features that an exchange site
is supposed to have.

# Getting started

To get started all you need to do is configure tow files:

	1. config.php
		- You can find this file in includes folder. Provide correct details of
		DB_HOST i.e Database host (normally 127.0.0.1),
		DB_NAME i.e Database name,
		DB_USER i.e Database user,
		DB_PASS i.e Database password

	2. fbconfig.php
		You need to setup a Facebook app on developer.facebook.com
		Then provide app_id, app_secret and correct $loginUrl in fbconfig.php file


Once you have done this you are all set to go.
