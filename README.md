**SimpleAPI**
===================


SimpleAPI is a REST API written in PHP, with a purpose of being a service easy to extend with additional modules and in accordiance with HTTP specification (using HTTP response codes and headers, for example).


Installation
-------------

###Requirements

> * PHP 5.4 or greater
> * MySQL 5.6 if using database functionality

The API does not require any additional installation steps to work. The only thing you'd probably like to look at is the configuration file (*config/default.conf.php*), explained below.


Configuration
----------------

**SYSTEM_RESPONSE_DEFAULT**

A *mime type* of a response, used by default, if none of the requested types are found, and the request accept any other response type (\*/\*). Otherwise, the response is printed to screen by PHP *print_r()* function.

**SYSTEM_MODULE_DEFAULT**

If empty, throws an error when no module name has been provided in the URL. If set, in case of lack of module name, API looks for one in this option and runs it.

**SYSTEM_LANGUAGE_DEFAULT**

If provided, API falls back to this language in case no language requested in Accept-Language header is found and the requests accept any other language (*).

**SYSTEM_ALLOW_CROSSORIGIN** <sup>Not Yet Supported</sup>

Security option, set to true if you want to allow requests from external domains (see [Wikipedia](https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS) for additional information).


Responders
------------------

Responders are functions, that parse raw input result from modules into a response with specified *mime type*. You can write your own responders, if you want your API to allow a response in format, that is not provided by default.

###Structure

```php
Responders::register('mime/type', function($data) {
	// do something with RAW data
	return $data;
});
```

First argument provided is a *string* of a *mime type* you want to register with provided function, or an *array* of *strings* with those.
Second argument is the function itself, which is then registered and called, if request asks for a response with a *mime type* provided in the first parameter.

> Responders with a *mime type* that already exist will overwrite those (with alphabetical priority of a file names).

###File

After preparing a file with a new responder, it needs to be saved in *system/responders/* directory with *{filename}.inc.php*, where *{filename}* is any correct name, that can be used in a filename (see note above).


Modules
--------------

###Structure

```PHP
/* modules/yourmodulename.mod.php */

namespace Module\yourmodulename;

class Module extends \MBase implements \MInterface {
	/* protected $_method */
	/* protected $_params */
	/* protected $_data */

	public function init() {
		// the code of your module

		return \Response::success('data_to_return');
	}
}
```

All module files need to be saved in *modules/* directory with a filename of *{modulename}.mod.php*.

###Understanding

All modules are in fact, definitions of a *Module* class, but in different *namespace*. This namespace needs to be provided first, in order for the module to load correctly.
The module itself needs to implement default module interface *\MInterface*, and can, but is not required to, inherit from *\MBase*, which is a default module class.

> *\MBase* object provides most of the API functionality that can be used in modules, along with providing it with HTTP method used, URL parameters and a list of usability functions.
> 
> If not used, *Module* object properties, shown above, will not exist.

Module needs to define at least one public function *init()*, which is called when the module is being loaded - that is the entry point for the whole module.
You are allowed to call any other method of this object from this point on, or load another module (see **Using multiple modules** section).

Data returned by this *init()* function will be the overall result of the module. This value can be of any type, like a *string* or an *array*, but using *\Response::success($data)* is recommended.

> Returning any value other than of *\Response* type will cause it to be converted to it by SimpleAPI. Mind that you can only return successful responses when not using *\Response* object.

If using *\MBase*, module gets access to additional class fields:

**<em>$_method</em>** - Currently used method ( DELETE | GET | OPTIONS | POST | PUT )

**<em>$_params</em>** - An array of URL parameters (separated by */* sign)

**<em>$_data</em>** - An array or object containing data sent to API with *PUT* / *DELETE* methods, equals *$_GET* if *GET* method used and *$_POST* if *POST* method used

###Using multiple modules

It is possible to use a different module, or multiple different modules when using one of them.

```PHP
public function init() {
	$name = 'someothermodule';
	$someOtherModule = \SimpleAPI::loadModule($name, $this->_params);

	return $someOtherModule;
}
```

This example module acts as a proxy, loading a different module with *$name* and providing it with current URL parameters.

```PHP
\Response \SimpleAPI::loadModule(string $name, (string | array) $params);
```

**<em>$name</em>** - The name of a module you want to load.

**<em>$params</em>** - A string (like */some/example/params*) or an array of strings, containing parameters you want to provide loaded module with.

It returns an object of *\Response* type, with the result.

> For security reasons, you cannot use the same module as the one you're calling from (to prevent infinite loop of recursion. Doing this will cause the calling module to fail with an error code of *'module-cannot-callback'*.


###Responses

You can use different kinds of responses to inform SimpleAPI of the result of your module.

```PHP
\Response \Response::success((string | int | array) $data);
```
Default result, takes one argument, with a result data.

```PHP
\Response \Response::error(string $message, (string | int) $errNo[, (string | int) $httpStatusCode]);
```
Response of error type can be used to inform the API that something gone wrong.

**<em>$message</em>** - Your error message to the user

**<em>$errNo</em>** - An error status code that can be used in your application to determine what exactly gone wrong.

**<em>$httpStatusCode</em>** - Optional, if not provided, HTTP status code *400 Bad Request* will be set.

The resulting object has following functionality:

**<em>getData()</em>** - In case of successful execution, returns the resulting data (*string*, *int* or *array*).

**<em>getState()</em>** - Returns current state of the response (*"success"* | *"error"*).

**<em>getCode()</em>** - Returns error code, in case of error, or 0 in case of success.

**<em>gerError()</em>** - Returns error message as a string or empty string, in case of success.

**<em>getHttpStatus()</em>** - Returns *int* code of a HTTP status set by requested module.

You can also use static functions:

**<em>translateHttpCode($code)</em>** - returns *string* if *\$code* is of *int* type (like *'Bad Request'* for *400*) and the other way, if *string* is provided. Returns *200* or *OK* if invalid value.

Built-in module functionality
-----------------------------

```PHP 
_setAllowedMethods((string | array) $methods)
```
Sets the allowed methods for a single module. By default, all methods are allowed.

```PHP
_getAllowedMethods()
```
Returns an array of methods allowed by the module.

```PHP
_getUsedMethod()
```
Returns a method, that is currently used in the module.

```PHP
_isMethodAllowed(string $method)
```
Returns true, if method provided in *$method* argument is set as allowed, or false if not. Can be used to exit module with error *\Response* object.

Language
---------

You can use multiple languages for your module, depending on the requested language. To translate a message to the user to his requested language, you have to use *\Lang* class.

```PHP
public function init() {
	return \Response::error(\Lang::get('some-custom-error'), 'some-custom-error', 403);
}
```
In this example, user will get a message with a text bound to *'some-custom-error'* ID in his requested language. To add a translation of the message in some language, a JSON file needs to be added to *locales/{lang_CODE}/* directory, where *{lang_CODE}* is a short code of a language (like *pl_PL* or *en_US*).
Multiple JSON files can be added to each directory, and all will be parsed by SimpleAPI (if translation already exists, it will be overwritten - filename alphabetical priority is used to read files).

###Language file structure

```JSON
{
	"some-custom-error": "An error ocurred. Please, contact the administrator.",
	"some-other-error": "Another one."
}
```

> Although any valid string can be used as ID, it is highly recommended to use *"module-modulename-msgcode"* syntax, to prevent unwanted overwriting of translations (SimpleAPI uses *"system-X-Y"* syntax for internal errors).

> Requested language is provided by *'Accept-Language'* header. It uses *'en-US'* syntax (dashes, instead of underscores), but it is converted to underscore syntax by SimpleAPI. 


License
---------

This is Open Source software! You can use and modify it however you want.