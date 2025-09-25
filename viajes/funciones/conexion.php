<?php
//se crea la variable $coenexion y se le asigna el resultado de 
//la conexión a la base de datos
//se le asigna el nombre de la base de datos agenda
$conexion= new mysqli("localhost","root","Hola1415","based");
//se verifica si la conexión fue exitosa
if($conexion){
   // echo"Conexión exitosa a la base de datos.";
}else{
    echo"Error en la conexion: ".$conexion->connect_error;
}
?>