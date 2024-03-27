ServerSocket
--------------------

.. include:: /api_en.desc/php/net/ServerSocket.header.rst

.. php:class:: php\\net\\ServerSocket

 Class SocketServer



**Methods**

----------

 .. php:method:: __construct($port = null, $backLog = 50)

  :param $port: :doc:`int </api_en/.types/int>` 
  :param $backLog: :doc:`int </api_en/.types/int>` 

 .. php:method:: accept()

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :returns: :doc:`php\\net\\Socket </api_en/php/net/Socket>` 

 .. php:method:: bind($hostname, $port, $backLog = 50)

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $hostname: :doc:`string </api_en/.types/string>` 
  :param $port: :doc:`int </api_en/.types/int>` 
  :param $backLog: :doc:`int </api_en/.types/int>` 

 .. php:method:: close()

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`


 .. php:method:: isClosed()

  :returns: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: isBound()

  Returns the binding state of the ServerSocket.

  :returns: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: setSoTimeout($timeout)

  Enable/disable SO_TIMEOUT with the specified timeout, in
  milliseconds.

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $timeout: :doc:`int </api_en/.types/int>` 

 .. php:method:: setReuseAddress($on)

  Enable/disable the SO_REUSEADDR socket option.

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $on: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: setReceiveBufferSize($size)

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $size: :doc:`int </api_en/.types/int>` 

 .. php:method:: setPerformancePreferences($connectTime, $latency, $bandWidth)

  Sets performance preferences for this ServerSocket.
  
  ! Not implemented yet for TCP/IP

  :param $connectTime: :doc:`int </api_en/.types/int>` 
  :param $latency: :doc:`int </api_en/.types/int>` 
  :param $bandWidth: :doc:`int </api_en/.types/int>` 

 .. php:staticmethod:: findAvailableLocalPort()

  :returns: :doc:`int </api_en/.types/int>` 



.. include:: /api_en.desc/php/net/ServerSocket.footer.rst

