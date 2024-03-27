Socket
--------------

.. include:: /api_en.desc/php/net/Socket.header.rst

.. php:class:: php\\net\\Socket

 Class Socket



**Methods**

----------

 .. php:method:: __construct($host = null, $port = null)

  :param $host: :doc:`null </api_en/.types/null>`, :doc:`string </api_en/.types/string>` 
  :param $port: :doc:`null </api_en/.types/null>`, :doc:`int </api_en/.types/int>` 

 .. php:method:: getOutput()

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :returns: :doc:`php\\io\\MiscStream </api_en/php/io/MiscStream>` 

 .. php:method:: getInput()

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`

  :returns: :doc:`php\\io\\MiscStream </api_en/php/io/MiscStream>` 

 .. php:method:: getLocalAddress()

  :returns: :doc:`string </api_en/.types/string>` 

 .. php:method:: getAddress()

  :returns: :doc:`string </api_en/.types/string>` 

 .. php:method:: getLocalPort()

  :returns: :doc:`int </api_en/.types/int>` 

 .. php:method:: getPort()

  :returns: :doc:`int </api_en/.types/int>` 

 .. php:method:: close()

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`


 .. php:method:: shutdownInput()

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`


 .. php:method:: shutdownOutput()

  **throws** :doc:`php\\io\\IOException </api_en/php/io/IOException>`


 .. php:method:: isConnected()

  :returns: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: isClosed()

  :returns: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: isBound()

  :returns: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: isInputShutdown()

  :returns: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: isOutputShutdown()

  :returns: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: connect($hostname, $port, $timeout = null)

  Connects this socket to the server

  :param $hostname: :doc:`string </api_en/.types/string>` 
  :param $port: :doc:`int </api_en/.types/int>` 
  :param $timeout: :doc:`null </api_en/.types/null>`, :doc:`int </api_en/.types/int>` 

 .. php:method:: bind($hostname, $port)

  Binds the socket to a local address.

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $hostname: :doc:`string </api_en/.types/string>` 
  :param $port: :doc:`int </api_en/.types/int>` 

 .. php:method:: bindDefault()


 .. php:method:: setSoTimeout($timeout)

  Enable/disable SO_TIMEOUT with the specified timeout, in
  milliseconds.

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $timeout: :doc:`int </api_en/.types/int>` 

 .. php:method:: setSoLinger($on, $linger)

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $on: :doc:`bool </api_en/.types/bool>` 
  :param $linger: :doc:`int </api_en/.types/int>` 

 .. php:method:: setReuseAddress($on)

  Enable/disable the SO_REUSEADDR socket option.

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $on: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: setReceiveBufferSize($size)

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $size: :doc:`int </api_en/.types/int>` 

 .. php:method:: setTcpNoDelay($on)

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $on: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: setKeepAlive($on)

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $on: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: setOOBInline($on)

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $on: :doc:`bool </api_en/.types/bool>` 

 .. php:method:: setSendBufferSize($size)

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $size: :doc:`int </api_en/.types/int>` 

 .. php:method:: setTrafficClass($tc)

  Sets traffic class or type-of-service octet in the IP
  header for packets sent from this Socket.

  :param $tc: :doc:`int </api_en/.types/int>` 

 .. php:method:: setPerformancePreferences($connectTime, $latency, $bandWidth)

  Sets performance preferences for this ServerSocket.
  
  ! Not implemented yet for TCP/IP

  :param $connectTime: :doc:`int </api_en/.types/int>` 
  :param $latency: :doc:`int </api_en/.types/int>` 
  :param $bandWidth: :doc:`int </api_en/.types/int>` 

 .. php:method:: sendUrgentData($data)

  Send one byte of urgent data on the socket. The byte to be sent is the lowest eight
  bits of the data parameter.

  **throws** :doc:`php\\net\\SocketException </api_en/php/net/SocketException>`

  :param $data: :doc:`int </api_en/.types/int>` 



.. include:: /api_en.desc/php/net/Socket.footer.rst

