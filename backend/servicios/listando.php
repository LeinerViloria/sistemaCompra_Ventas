<?php

function conectar($ruta=0){
    if($ruta==0){
        require_once '../backend/componentes/conectar.php';
    }else if($ruta==1){
        require_once '../componentes/conectar.php';
    }else if($ruta==2){
        require_once './backend/componentes/conectar.php';
    }
    $conexionMySQL = new conectar("localhost", "detodosparatodos", "root", "");   
    return $conexionMySQL->getConexion();
}

function buscar($conexion, $tabla, $consulta=0, $consulta_personalizada=null){
    //Consulta = 0 => SELECT *
    //Consulta = 1 => Arma tu consulta
    if($consulta==0){
        $sentencia = "SELECT * FROM $tabla";
    }else if($consulta==1){
        $sentencia = $consulta_personalizada;
    }
    $sentencia = $conexion->prepare($sentencia);
    $sentencia->execute();

    return $sentencia->fetchAll(PDO::FETCH_ASSOC);
}

function obtener_perfiles(){         
    $conexion = conectar();

    $tabla="perfiles";

    $perfiles = buscar($conexion, $tabla);
    
    return $perfiles;
}

function obtener_empleados(){
    $conexion = conectar();

    $tabla = "";

    $sql="SELECT e.id, p.nombre perfil, e.nombres, e.apellidos, u.correo correo, e.telefono
    FROM empleados e, perfiles p, usuarios u
    WHERE e.perfil_id=p.id AND e.id=u.id_empleado
    ORDER BY 3, 4";

    $empleados = buscar($conexion, $tabla, 1, $sql);

    return $empleados;
}

function obtenerPassword($pk, $id){    
    $conexion = conectar(1);
    $tabla = "";
    $sql = "SELECT password FROM usuarios WHERE $pk=$id";
    $pass = buscar($conexion, $tabla, 1, $sql);
    return $pass;           
}

function buscando_id($email){
    $conexion = conectar(1);
    $tabla = "";

    $sql ="SELECT id_empleado
        FROM usuarios
        WHERE correo='$email'";
    
    $id_encontrado = buscar($conexion, $tabla, 1, $sql);
    return $id_encontrado;
}

function verificando_identidad($id, $email){
    $id_encontrado = buscando_id($email);

    if(!empty($id_encontrado)){        
        
        if($id_encontrado[0]['id_empleado']==$id){
            return true;            
        }else{
            return false;
        }
                
    }else{
        return true;
    }
}

function obtener_empleado_registrado($id){         
    $conexion = conectar(1);
    $tabla = "";

    $sql ="SELECT *
        FROM empleados
        WHERE id='$id'";
    
    $empleado = buscar($conexion, $tabla, 1, $sql);

    return $empleado;
}

function obtener_redes($nombre){
    $conexion = conectar(1);
    $tabla="";

    $sql="SELECT codigo FROM redes_sociales WHERE nombre='$nombre' LIMIT 1";
    $codigo = buscar($conexion, $tabla, 1, $sql);

    return $codigo;
}

function obtener_clientes($empleado){
    $conexion = conectar();

    $tabla="";

    $sql = "SELECT id, CONCAT(nombres, ' ', apellidos) cliente
        FROM clientes
        WHERE id_Empleado='$empleado' OR id_empleado IN (SELECT id FROM empleados WHERE perfil_id='A1')
        ORDER BY nombres, apellidos";

    $clientes = buscar($conexion, $tabla, 1, $sql);

    return $clientes;

}

function obtener_total_clientes(){
    $conexion = conectar();
    $tabla="";

    $sql="SELECT COUNT(1) total FROM clientes";
    $total = buscar($conexion, $tabla, 1, $sql);

    return $total;
}

function obtener_numero_clientes($empleado){
    $conexion = conectar();
    $tabla="";

    $sql="SELECT COUNT(1) numero
        FROM clientes
        WHERE id_Empleado='$empleado'       
        GROUP BY id_Empleado";
    $numero = buscar($conexion, $tabla, 1, $sql);

    return $numero;
}

function obteniendo_porcentaje_actual(){
    $conexion = conectar();

    $tabla="";    

    $sql="SELECT valor, año
        FROM porcentaje_anual
        WHERE año=YEAR(CURDATE())";

    $porcentaje = buscar($conexion, $tabla, 1, $sql);        

    if(empty($porcentaje)){
        //Al entrar aqui es porque en el año actual no hay registro
        //Entonces se busca a ver si hay registro del año pasado
        
        $sql="SELECT valor, año
        FROM porcentaje_anual
        WHERE año<YEAR(CURDATE())
        ORDER BY 2 DESC
        LIMIT 1";

        $porcentaje = buscar($conexion, $tabla, 1, $sql);        
    }

    return $porcentaje;

    
}

function obtener_ultima_hora(){
    $conexion = conectar(1);

    $tabla="";    

    $sql="SELECT momento_registro
        FROM porcentaje_anual
        WHERE año=CURRENT_DATE()";

    $hora = buscar($conexion, $tabla, 1, $sql);            

    return $hora;
}

function obtener_porcentaje_anual(){
    $conexion = conectar();

    $tabla="";    

    $sql = "SELECT valor
    FROM porcentaje_anual
    WHERE año=CURRENT_DATE();";

    $valor = buscar($conexion, $tabla, 1, $sql);

    return $valor;
    
}

function familias($ruta=0){

    $conexion= conectar($ruta);

    $tabla = "";

    $sql="SELECT id, nombre
            FROM familias
            ORDER BY 2";

    $familias=buscar($conexion, $tabla, 1, $sql);

    return $familias;
}

function proveedores(){
    $conexion = conectar();
    
    $tabla = "provedores";

    $proveedores = buscar($conexion, $tabla);

    return $proveedores;
}

function insertandoProducto($producto){
    $conexion = conectar(1);
    $tabla="";
    $busqueda = "SELECT COUNT(1) cantidad FROM productos WHERE id='$producto->id'";
    $resultado=buscar($conexion, $tabla, 1, $busqueda);
        
    if(!empty($resultado) && $resultado[0]['cantidad']!=0){
        $sql=$conexion->prepare("UPDATE productos SET familia_id='$producto->id_familia', imagen='$producto->imagen', nombre='$producto->nombre', precio_compra=$producto->precioCompra, precio_ventas=$producto->precioVenta, stock=$producto->stock, descripcion='$producto->descripcion' WHERE id='$producto->id'");
    }else{
        $sql=$conexion->prepare("INSERT INTO productos VALUES(:id, :familia_id, :imagen, :nombre, :precio_Compra, :precioVentas, :stock, :descripcion)");
        $sql->bindParam(':id', $producto->id, PDO::PARAM_STR);
        $sql->bindParam(':familia_id', $producto->id_familia, PDO::PARAM_STR);
        $sql->bindParam(':imagen', $producto->imagen, PDO::PARAM_LOB);
        $sql->bindParam(':nombre', $producto->nombre, PDO::PARAM_STR);
        $sql->bindParam(':precio_Compra', $producto->precioCompra, PDO::PARAM_STR);
        $sql->bindParam(':precioVentas', $producto->precioVenta, PDO::PARAM_STR);
        $sql->bindParam(':stock', $producto->stock, PDO::PARAM_STR);
        $sql->bindParam(':descripcion', $producto->descripcion, PDO::PARAM_STR);
    }       

    return $sql->execute();
}