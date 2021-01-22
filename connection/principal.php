<?php
    include("db-connection.php");
    include("correo.php");

    $option = $_POST['opt'];

    $con = conection();
    
    switch($option){
        case 1:
            listmenu($con);
            break;
        case 2:
            $name = $_POST['prod'];
            menuitem($con, $name);
            break;
        case 3:
            session_start();
            $email= $_SESSION['email'];
            usrdata($con, $email);
            break; 
        case 4:
            sregister($con);  
            break; 
        case 5:
            sorder($con);
            break;
        case 6:
            scater($con);
            break;
        case 7:
            spublication($con);
            break;
        case 8:
            $name = $_POST['mail'];
            $registro=$_POST['reg'];
            corder($con, $name, $registro);  
            break;
        case 9:
            $inferior= $_POST['inferior'];
            $tamanio= $_POST['tamanio'];
            ccatering($con, $inferior, $tamanio);
            break;
        case 10:
            login($con);  
            break; 
        case 11:
            bringpub($con);
            break;    
        case 12:
            $rol = $_POST['rol'];
            verificarRolSession($rol);
            break;
        case 13:
            $no_solicitud= $_POST['no_solicitud'];
            $estado= $_POST['estado'];
            actualizarEstadoCatering($con, $no_solicitud, $estado);
        case 14:
            session_start();
            session_unset();
            session_destroy();
            die();
        default:
            echo "Error";
    }

    function listmenu($con){
        $result =request(1, $con, NULL);
        #(solo pueba) muestra en una lista los productos que se encuentran en el menu
        while($row = mysqli_fetch_array($result)){
            $product = $row["prod"];
            echo "Producto: ".$product."<br/>";
        }
    }

    #obtiene el valor de una campo especificado previamente
    function menuitem($con, $name,$registro){
        $result = request(2, $con, $name);
        $row = mysqli_fetch_row($result);
        #muestra un atrubuto de la tabla 'menu', definido por $reg en $row["{$reg}"].
        #$reg = columna que se desea visualizar: 0-nombre del producto, 1-precio del producto
        echo $row["{$registro}"];
        return $row["{$registro}"];
    }

   #obtiene la información de 1 usuario en específico.  
    function usrdata($con, $email){
        $result = request(3, $con, $email);
        $vars= array('nom', 'ln', 'mail', 'phone');
        $encabezados= array('Nombre', 'Apellido', 'Correo', 'Tel&eacute;fono');
        #muestra un atrubuto de la tabla 'profiledata', definido por $reg en $row["{$reg}"].
        #$reg = columna que se desea visualizar: 0-correo, 1-contraseña, 2-rol, 3-nombre, 4-apellido, 5-telefono
       // echo $row["{$registro}"];

        #se muestra toda la información del usuario
        while($list = mysqli_fetch_array($result)){
            for ($i=0; $i<4; $i++){
                echo '<div class="col-lg-4 col-md-6 form-group">' .
                        '<label>' . $encabezados[$i] . ': </label>' .
                        '<input type="text" name="' . $vars[$i] . '" class="form-control" id="' . $vars[$i] . '" value="' . $list[$vars[$i]] . '" disabled />' .
                    '</div> <br />';
            }
        }  
        #return $row;
    }

    #registra a un usuario
    function sregister($con){
        $vars= array('email', 'contrasenia', 'name', 'lastname', 'phone');
        #obtiene los valores del formulario para registro (nombre de los campos)
        for ($i=0; $i<5; $i++){
            $list[$i] = $_POST["{$vars[$i]}"];
        }
        register($con, $list);
        echo "Registro exitoso";
    }

    #registra a un usuario
    function login($con){
        $mail = $_POST['email'];
        $contr = $_POST['contrasenia'];

        $sesion= login_f($con,$mail,$contr);
        if($sesion != 0) {
            session_start();
            $_SESSION['email']= $mail;
            if($sesion == 1){
                $_SESSION['rol']= "admin";
            }else {
                $_SESSION['rol']= "client";
            }
        }
    }

    #guarda una orden
    function sorder($con){
        $vars=array('mail', 'msj', 'total');
        #obtiene los valores del formulario para orden (nombre de los campos)
        for ($i=0; $i<3; $i++){
            $list[$i] = $_POST["{$vars[$i]}"];
        }
        order($con,$list);
    }

    #obtiene la información de una orden específica
    function corder($con, $name, $registro){
        $result = request(4, $con, $name);
        $vars=array('norder','omail', 'stat', 'msg', 'bill');

        #(prueba)se muestra toda la información del usuario
        while($row = mysqli_fetch_array($result)){
            for ($i=0; $i<5; $i++){
                $list[$i] = $row["{$vars[$i]}"];
                echo $list[$i]."<br/ >";
            }
        }  
        #return $row;
    }

    #guarda orden de catering
    function scater($con){
        session_start();
        if(!isset($_SESSION)) {
            echo "<script>alert('Sesión no reconocida');</script>";
            //header("Location: http://google.com/search?Sesión%20No%20Reconocida");
            die();
        }

        $email= $_SESSION['email'];

        $lugar= $_POST['lugar'];
        $no_paquete= $_POST['no_paquete'];
        $descripcion= $_POST['descripcion'];

        $response= registrarCatering($con, $email, $no_paquete);
        //traemos datos cliente para correo
        $response2= getNombreCliente($con, $email);
        $nombre_cliente= "";
        while($row = mysqli_fetch_array($response2)){
            $nombre_cliente= $row["nom"];
        }
        enviarCorreoCatering($nombre_cliente, $email, $no_paquete, $lugar, $descripcion);
        echo "Registro exitoso";
    }

    #trae los pedidos de catering
    function ccatering($con, $inferior, $tamanio) {
        $result = getPedidosCatering($con, $inferior, $tamanio);
        $vars=array('nsolic','cmail', 'npack', 'dtime', 'state');

        #se muestra toda la información del usuario
        echo 
        "<th colspan='5'>Pedidos de catering</th>" .
        "<tr>" .
            "<td>No. de solicitud</td>" .
            "<td>Correo de contacto</td>" .
            "<td>No. de paquete</td>" .
            "<td>Fecha</td>" .
            "<td>Estatus</td>" .
        "</tr>";
        while($row = mysqli_fetch_array($result)){
            for ($i=0; $i < 5; $i++){
                $list[$i] = $row["{$vars[$i]}"];
            }
            echo
            "<tr>" . 
                "<td>" . $list[0] . "</td>" .
                "<td>" . $list[1] . "</td>" .
                "<td>" . $list[2] . "</td>" .
                "<td>" . $list[3] . "</td>" .
                "<td>" .
                    "<select id='catering_cat_" . $list[0] . "' class='select-control' onchange='actualizarEstadoPedidoCatering(this)'>";
            if($list[4] == "Recibido") {
                echo "<option value='Recibido' class='select-items' selected>Recibido</option>" .
                    "<option value='En Proceso' class='select-items'>En Proceso</option>" .
                    "<option value='Finalizado' class='select-items'>Finalizado</option>"; 
            }else if($list[4] == "En Proceso") {
                echo "<option value='Recibido' class='select-items'>Recibido</option>" .
                    "<option value='En Proceso' class='select-items' selected>En Proceso</option>" .
                    "<option value='Finalizado' class='select-items'>Finalizado</option>";
            }else if($list[4] == "Finalizado") {
                echo "<option value='Recibido' class='select-items'>Recibido</option>" .
                    "<option value='En Proceso' class='select-items'>En Proceso</option>" .
                    "<option value='Finalizado' class='select-items' selected>Finalizado</option>";
            }      
            echo
                    "</select>" .
                "</td>" .
            "</tr>";
        }  
    }
    
    function actualizarEstadoCatering($con, $no_solicitud, $estado) {
        $result = actualizarEstadoCateringBD($con, $no_solicitud, $estado);
    }

    function spublication($con){   
        if (count($_FILES) > 0) {
			if (is_uploaded_file($_FILES['userImage']['tmp_name'])) {
				$imgData = addslashes(file_get_contents($_FILES['userImage']['tmp_name']));
				$query = "INSERT INTO pubs(pmail, img) VALUES('{$_POST['mail']}','{$imgData}')";
				$result = mysqli_query($con, $query) or die("<b>Error:</b> Problem on Image Insert<br/>" . mysqli_error($con));
				if (isset($current_id)) {
					header("Location: listImages.php");
				}
			}
		}
    }

    function bringpub(){
        $result = $con->query("SELECT MAX(nimg) AS btm FROM pubs");
        $value = mysqli_fetch_array($result);
        
        $result = request(6,$con,$value["btm"]);

        if($result->num_rows > 0){
            $imgData = $result->fetch_assoc();
            
            //Render image
            header("Content-Type: image/jpeg"); 
            echo $imgData['img']; 
        }else{
            echo 'Image not found...';
        }
    }

    function verificarRolSession($rol){
        session_start();
        if($_SESSION['rol'] == $rol) {
            echo true;
        }
        echo false;
    }
    mysqli_close($con);
?>