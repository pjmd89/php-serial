# php-serial
Establece una conexión con un puerto serial.

Esta clase esta escrita en PHP y a diferencia de otras clases escritas en el mismo lenguaje esta no detiene la lectura del buffer en dependencia al tamaño de la cadena sino que espera el bloqueo y en ese instante devuelve el mensaje capturado en el buffer.

El bloqueo viene dado por un valor NULL en el puerto serie en el momento que deja de recibir datos en el buffer del puerto serie. Este valor NULL es capturado y definido como fin del mensaje. En este momento se retorna el valor de la cadena.

El valor de la cantidad de bloqueos a recibir puede ser definido desde el método read a fin de establecer cuantos bloqueos quiera esperar antes de retornar el valor de la cadena el mensaje. También puede que el puerto serie esté enviando un valor NULL sin necesidad de que sea el bloqueo.

Ejemplo de envio de mensaje por medio de comandos AT:

include 'serial.php';

$serial = new Serial();

$serial->write( 'AT+CMGF=1' );

$serial->write( chr( 13 ) );

$telefono = "ESCRIBA AQUI EL NUMERO TELEFONICO";

$serial->write( 'AT+CMGS="'.$telefono.'"' );

$serial->write( chr( 13 ) );

$serial->write('este es otro sms');

$serial->write(chr(26));

echo $serial->read( 4 );

# Configuracion por defecto del puerto serie
 		Puerto: /dev/ttytAMA0
 		Baud rate: 57600
 		Data:8
 		Parity:None
 		Stop Bits:1
