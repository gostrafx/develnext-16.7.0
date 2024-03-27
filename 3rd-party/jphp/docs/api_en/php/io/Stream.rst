Stream
-------------

.. include:: /api_en.desc/php/io/Stream.header.rst

.. php:class:: php\\io\\Stream

 **abstract** class

**Children**

----------------------

 * **class** :doc:`php\\io\\FileStream </api_en/php/io/FileStream>`
 * **class** :doc:`php\\io\\MiscStream </api_en/php/io/MiscStream>`
 * **class** :doc:`php\\io\\ResourceStream </api_en/php/io/ResourceStream>`
 * **class** :doc:`php\\net\\NetStream </api_en/php/net/NetStream>`



**Properties**

----------

 .. php:attr:: path

  :doc:`string </api_en/.types/string>`

  **private**


 .. php:attr:: mode

  :doc:`string </api_en/.types/string>`

  **private**




**Methods**

----------

 .. php:method:: getPath()

  :returns: :doc:`string </api_en/.types/string>` 

 .. php:method:: getMode()

  :returns: :doc:`string </api_en/.types/string>` 

 .. php:method:: read($length)

  **abstract**


  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :param $length: :doc:`int </api_en/.types/int>`  - - count of bytes
  :returns: :doc:`mixed </api_en/.types/mixed>` 

 .. php:method:: readFully()

  **abstract**


  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :returns: :doc:`mixed </api_en/.types/mixed>` 

 .. php:method:: write($value, $length = null)

  **abstract**


  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :param $value: :doc:`string </api_en/.types/string>` 
  :param $length: :doc:`null </api_en/.types/null>`, :doc:`int </api_en/.types/int>` 
  :returns: :doc:`int </api_en/.types/int>` 

 .. php:method:: eof()

  **abstract**


  :returns: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: seek($position)

  **abstract**


  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :param $position: :doc:`int </api_en/.types/int>` 
  :returns: :doc:`mixed </api_en/.types/mixed>` 

 .. php:method:: getPosition()

  **abstract**


  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :returns: :doc:`int </api_en/.types/int>` 

 .. php:method:: close()

  **abstract**


  :returns: :doc:`mixed </api_en/.types/mixed>` 

 .. php:method:: __construct($path, $mode = null)

  :param $path: :doc:`string </api_en/.types/string>` 
  :param $mode: :doc:`null </api_en/.types/null>`, :doc:`string </api_en/.types/string>` 
  :returns: :doc:`php\\io\\Stream </api_en/php/io/Stream>` 

 .. php:method:: setContext($context)

  :param $context: 
  :returns: :doc:`void </api_en/.types/void>` 

 .. php:method:: getContext()

  :returns: :doc:`mixed </api_en/.types/mixed>` 

 .. php:staticmethod:: of($path, $mode = 'r')

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :param $path: :doc:`string </api_en/.types/string>` 
  :param $mode: :doc:`string </api_en/.types/string>` 
  :returns: :doc:`php\\io\\Stream </api_en/php/io/Stream>` 

 .. php:staticmethod:: getContents($path, $mode = 'r')

  Create a stream, call and return the result of the readFully() method, finally call the close() method.

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :param $path: :doc:`string </api_en/.types/string>` 
  :param $mode: :doc:`string </api_en/.types/string>` 
  :returns: :doc:`string </api_en/.types/string>` binary

 .. php:staticmethod:: putContents($path, $data, $mode = 'w+')

  Create a stream, call and return the result of the write() method, finally call the close() method.

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :param $path: :doc:`string </api_en/.types/string>` 
  :param $data: :doc:`string </api_en/.types/string>` 
  :param $mode: :doc:`string </api_en/.types/string>` 

 .. php:staticmethod:: tryAccess($path, $onAccess, $mode = 'r')

  Open a stream and close it after calling $onAccess automatically.

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :param $path: :doc:`string </api_en/.types/string>` 
  :param $onAccess: :doc:`callable </api_en/.types/callable>`  - (Stream $stream)
  :param $mode: :doc:`string </api_en/.types/string>` 

 .. php:staticmethod:: exists($path)

  Checks stream is exists. It tries to open a stream and if all is ok, returns true and closes it.

  **throws** :doc:`php\\io\\\\Exception </api_en/php/io//Exception>` if you check external streams like http or ftp

  :param $path: :doc:`string </api_en/.types/string>` 
  :returns: :doc:`bool </api_en/.types/bool>` 

 .. php:staticmethod:: register($protocol, $className)

  :param $protocol: :doc:`string </api_en/.types/string>` 
  :param $className: :doc:`string </api_en/.types/string>` 

 .. php:staticmethod:: unregister($protocol)

  :param $protocol: 



.. include:: /api_en.desc/php/io/Stream.footer.rst

