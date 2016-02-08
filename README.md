FileCounter
===========

A PHP counter for web pages that doesn't use a database.  The focus is on efficient file writes and readable logs.


Features
--------
* No database.
* Quick writes.
* Colour highlighting for different days, alternate rows, and mouse hover.
* Auto generated links reload logs and toggle chopping long values and/or hiding repeated values.
* Optional parameters can be used to log page hits in the counter for that page, and/or the "all" counter.
* Simple blocking of IP ranges, to prevent bots from soaking up bandwidth (and blocked hits will be logged separately).  
The file named "_" is the empty dummy file the bots get redirected to.
* Row or column ordering in the log display could be changed by adjusting a bit of code.
For example, not every log column that is saved is displayed
(because my current server doesn't offer some HTTP info that others do) and the most recent rows are at the top.  Preferences vary.


Requirements
------------
* PHP 5.2+  
Some PHP versions have backward incompatibile changes, so though I expect this works on newer installs, I have not tested it.


Screenshots
-----------

listing of logs
---------------
![Screenshot](/screenshot-listing.JPG)


sample log
----------

default display: long values are shortened, repeats are replaced with a tilde

![Screenshot](/screenshot-1.JPG)


repeats shown

![Screenshot](/screenshot-2.JPG)


repeats shown, long values are intact

![Screenshot](/screenshot-3.JPG)



Possible future features
------------------------
* global var for blocked IP ranges 
* dynamic sorting on log columns



Thoughts
--------
All the code is in a single file.  There are advantages to splitting it up.  As often, personal style is a factor.

No code is automatically executed.  The including file must call the intended function.

The data file structure and other aspects are detailed in the comments in fileCounter.php.

Improvements, bouquets and brickbats are welcome.


License
-------
Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
