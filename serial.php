/**
 * Clase serial para interconexión serial en linux
 * @package serial
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @author Pablo Muñoz <pjmd89@gmail.com>
 */

<?php
/**
 * Establece una conexión, lee y escribe datos en un puerto serial dado.
 * 
 * A diferencia de otras clases, esta detiene su lectura de datos en el buffer
 * del puerto serie en dependencia de la cantidad de bloqueos encontrados. Es
 * decir, no limita la lectura al tamaño de la cadena en bits sino que detiene la lectura
 * en el momento que Rx deja de recibir datos. La cantidad de bloqueos (sin respuesta 
 * en Rx) se puede definir en el método read. Para mas información leer método read
 * 
 *                    ---------------ADVERTENCIA---------------
 * Esta clase depende de la herramienta "stty" y su uso solo ha sido probado en GNU/Linux
 * 
 * Configuración del puerto serie por defecto:
 * 		Puerto: /dev/ttytAMA0
 * 		Baud rate: 57600
 * 		Data:8
 * 		Parity:None
 * 		Stop Bits:1
 *
 * @package serial
 * @version 1.0
 * 
 */
Class Serial{
		
	private $_handler;
	
	private $_device;
	
	private $_is_opened = false;
	
	private $_device_exists;
	
	private $_stty_exists;
	
	private $_error;
	
	private $_message_error;
	
	private $_baud_rate;
	
	private $_speed;
	
	private $_special_settings;
	
	private $_control_settings = 'cs8 -cread -parenb';
	
	private $_input_settings = '-ignpar -cstopb -iutf8';
	
	private $_output_settings = '';
	
	private $_local_settings = '-echo';
	
	private $_combination_settings = 'raw';
	
	private $_timeout;
	
	private $_stream_timeout;
	
	/**
	 * Constructior del objeto
	 *
	 * @param string $device establece el puerto serie. Valor por defecto: '/dev/ttyAMA0'.
	 * @param int $baud_rate establece los baudios del puerto serie. Valor por defecto: 57600
	 * @param int $timeout establece el tiempo en segundos de ejecución máxima del puerto serie en bloqueo. Valor por defecto: 15
	 * @return void
	 */
	public function __construct( $device = '/dev/ttyAMA0' , $baud_rate = 57600 , $timeout = 15 ){
		
		$this->_device = $device;
		
		$this->_baud_rate = $baud_rate;
		
		$this->_is_opened = false;
		
		$this->_error = false;
		
		$this->_speed = 'ispeed '.$baud_rate.' ospeed '.$baud_rate;
		
		$this->_timeout = $timeout;
		
		$this->_message_error = [];
	}
	
	/**
	 * Comprueba que el puerto serie exista y se tenga acceso de lectura y escritura.
	 *
	 * @return void
	 */
	private function _device_exists(){
		
		$return = false;
		
		$this->_message_error['device'] = 'The device '.$this->_device.' do not exists, not readable or not writable';
		
		if(is_readable( $this->_device ) && is_writable( $this->_device)){
			
			$return = true;
			
			unset( $this->_message_error['device'] );
		}
		
		$this->_device_exists = $return;
	}
	
	/**
	 * Comprueba que el comando "stty" exista
	 *
	 * @return void
	 */
	private function _stty_exists( ){
		
		$returned = shell_exec( 'stty --version' );
		
		$exists = true;
		
		if($returned == '' ){
			
			$exists = false;
			
			$this->_message_error['stty'] = 'The stty command do not exists';
			
			
		}
		$this->_stty_exists = $exists;
	}
	/**
	 * Configura el puerto serie. Hace uso de la herramienta "stty"
	 *
	 * @return void
	 */
	private function _config( ){
		
		$command = 'stty -F ' . $this->_device . ' ' . 
			$this->_combination_settings . ' ' .
			$this->_speed . ' ' .  
			$this->_special_settings . ' ' .
			$this->_control_settings . ' ' .
			$this->_input_settings . ' ' .
			$this->_output_settings . ' ' .
			$this->_local_settings;
		
		shell_exec( $command );
	}
	/**
	 * Apertura el puerto serie, de lo contrario genera mensajes de error
	 *
	 * @return void
	 */
	private function _open( ){
		
		if( !$this->_is_opened ){
			
			$this->_message_error = [];
			
			$this->_device_exists();
			
			$this->_stty_exists();
			
			if( $this->_stty_exists && $this->_device_exists ){
				
				$this->_config();
				
				$this->_is_opened = true;
				
				$this->_handler = fopen( $this->_device , 'w+' );
				
				stream_set_blocking($this->_handler, 0);
				
				stream_set_timeout($this->_handler, $this->_timeout );
		
				$this->_stream_timeout = stream_get_meta_data($this->_handler);
				
			}
			else{
				
				$this->_is_opened = false;
				
				echo join(' and ', $this->_message_error)."\n";
			}
		}
	}
	/**
	 * Establece la Configuración especial del comando "stty".
	 * Para mas información leer la documentación de stty en la seccion "Special Settings"
	 *
	 * @param string $settings establece los valores de las variables espciales
	 * @return void
	 */
	public function set_special_settings( $settings ){
		
		$this->_special_settings = $settings;
	}
	/**
	 * Establece la Configuración de control del comando "stty".
	 * Para mas información leer la documentación de stty en la seccion "Control Settings"
	 *
	 * @param string $settings establece los valores de las variables de control
	 * @return void
	 */
	public function set_control_settings( $settings ){
		
		$this->_control_settings = $settings;
	}
	/**
	 * Establece la Configuración de input del comando "stty".
	 * Para mas información leer la documentación de stty en la seccion "Input Settings"
	 *
	 * @param string $settings establece los valores de las variables de input
	 * @return void
	 */
	public function set_input_settings( $settings ){
		
		$this->_input_settings = $settings;
	}
	/**
	 * Establece la Configuración de output del comando "stty".
	 * Para mas información leer la documentación de stty en la seccion "Output Settings"
	 *
	 * @param string $settings establece los valores de las variables de output
	 * @return void
	 */
	public function set_output_settings( $settings ){
		
		$this->_output_settings = $settings;
	}
	/**
	 * Establece la Configuración de local del comando "stty".
	 * Para mas información leer la documentación de stty en la seccion "Local Settings"
	 *
	 * @param string $settings establece los valores de las variables de local
	 * @return void
	 */
	public function set_local_settings( $settings ){
		
		$this->_local_settings = $settings;
	}
	/**
	 * Establece la Configuración de combination del comando "stty".
	 * Para mas información leer la documentación de stty en la seccion "Combination Settings"
	 *
	 * @param string $settings establece los valores de las variables de combination
	 * @return void
	 */
	public function set_combination_settings( $settings ){
		
		$this->_combination_settings = $settings;
	}
	/**
	 * Escribe un mensaje en el puerto serie.
	 *
	 * @param string $message Mensaje a enviar al puerto serie
	 * @return void
	 */
	public function write( $message ){
		
		$this->_open();
		
		if($this->_is_opened){
			
			fwrite( $this->_handler , $message );
			
			usleep( 100000 );
		}
		
	}
	/**
	 * Lee el puerto serie.
	 *
	 * Este método NO define el tamaño del buffer de lectura, por el contrario utiliza los bloqueos como referencia
	 * para detener la lectura del buffer.
	 * Esta clase no bloquea la lectura de datos en el handler, en sustitoción de esto, siempre está leyendo desde Rx y,
	 * de no conseguir datos, el puerto serie devuelve NULL. Este NULL se cuenta como bloqueo.
	 * Tenga en cuenta que al leer el puerto serial este puede estar enviado un valor NULL y este se cuenta como
	 * un bloqueo. Si su dispositivo envia valor NULL, aumente el número de bloqueos en $blocks.
	 * 
	 * @param int $blocks Define la cantidad de bloqueos que debe conseguir antes de terminar la lectura en el puerto serie
	 * @return string Mensaje recibido desde el puerto serie
	 */
	public function read( $blocks = 1 ){
		
		$this->_open();
		
		$return = '';
		
		if($this->_is_opened){
			
			$eof = false;
			
			$count_blocks = 0;
			
			$last_chr = null;
			
			while( ( !$eof ) && ( !$this->_stream_timeout['timed_out'] ) ){
				
				$chr = fgetc($this->_handler);
				
				if( ord( $chr ) == 0 && ord($last_chr) != 0){
					
					++$count_blocks;
				}
				
				$last_chr = $chr;
				
				if( $count_blocks == $blocks ){
					
					$eof = true;
				}
				
				if( ord($chr) == 0 ){
					
					$chr = '';
				}
				
				$return .= $chr;
			}
		}
		
		return $return;
	}
}
?>
