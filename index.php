<?php

include 'config.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

if(isset($_POST['register'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);
   $cpass = sha1($_POST['cpass'] );
   $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

   $select_user = $conn->prepare("SELECT * FROM `user` WHERE name = ? AND email = ?");
   $select_user->execute([$name, $email]);

   if($select_user->rowCount() > 0){
      $message[] = '¡El nombre de usuario o el correo electrónico ya existe!';
   }else{
      if($pass != $cpass){
         $message[] = '¡La contraseña no coincide!';
      }else{
         $insert_user = $conn->prepare("INSERT INTO `user`(name, email, password) VALUES(?,?,?)");
         $insert_user->execute([$name, $email, $cpass]);
         $message[] = 'Registrado exitosamente, inicie sesión ahora por favor';
      }
   }

}

if(isset($_POST['update_qty'])){
   $cart_id = $_POST['cart_id'];
   $qty = $_POST['qty'];
   $qty = filter_var($qty, FILTER_SANITIZE_STRING);
   $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
   $update_qty->execute([$qty, $cart_id]);
   $message[] = 'Cantidad del carrito actualizado';
}

if(isset($_GET['delete_cart_item'])){
   $delete_cart_id = $_GET['delete_cart_item'];
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
   $delete_cart_item->execute([$delete_cart_id]);
   header('location:index.php');
}

if(isset($_GET['logout'])){
   session_unset();
   session_destroy();
   header('location:index.php');
}

if(isset($_POST['add_to_cart'])){

   if($user_id == ''){
      $message[] = '¡Por favor, inicia sesión primero!';
   }else{

      $pid = $_POST['pid'];
      $name = $_POST['name'];
      $price = $_POST['price'];
      $image = $_POST['image'];
      $qty = $_POST['qty'];
      $qty = filter_var($qty, FILTER_SANITIZE_STRING);

      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND name = ?");
      $select_cart->execute([$user_id, $name]);

      if($select_cart->rowCount() > 0){
         $message[] = 'Ya agregado al carrito';
      }else{
         $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
         $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
         $message[] = '¡Añadido al carrito!';
      }

   }

}

if(isset($_POST['order'])){

   if($user_id == ''){
      $message[] = '¡Por favor, inicia sesión primero!';
   }else{
      $name = $_POST['name'];
      $name = filter_var($name, FILTER_SANITIZE_STRING);
      $number = $_POST['number'];
      $number = filter_var($number, FILTER_SANITIZE_STRING);
      $address = 'flat no.'.$_POST['flat'].', '.$_POST['street'].' - '.$_POST['pin_code'];
      $address = filter_var($address, FILTER_SANITIZE_STRING);
      $method = $_POST['method'];
      $method = filter_var($method, FILTER_SANITIZE_STRING);
      $total_price = $_POST['total_price'];
      $total_products = $_POST['total_products'];

      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
      $select_cart->execute([$user_id]);

      if($select_cart->rowCount() > 0){
         $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, method, address, total_products, total_price) VALUES(?,?,?,?,?,?,?)");
         $insert_order->execute([$user_id, $name, $number, $method, $address, $total_products, $total_price]);
         $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
         $delete_cart->execute([$user_id]);
         $message[] = 'Pedido realizado con éxito';
      }else{
         $message[] = '¡tu carrito está vacío';
      }
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Sra Clarita</title>
   <link rel="shortcut icon" href="images/iconartesania.png" type="image/x-icon">
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- css  -->
   <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

<?php
   if(isset($message)){
      foreach($message as $message){
         echo '
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
?>

<!-- HEADER  -->

<header class="header">

   <section class="flex">

      <a href="#home" class="logo"><span>Sra </span>Clarita.</a>

      <nav class="navbar">
         <a href="#home">Inicio</a>
         <a href="#menu">Productos</a>
         <a href="#order">Ordenar</a>
      </nav>

      <div class="icons">
         <div id="menu-btn" class="fas fa-bars" style="color:white"></div>
         <?php
            $count_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $count_cart_items->execute([$user_id]);
            $total_cart_items = $count_cart_items->rowCount();
         ?>
         <div id="cart-btn" class="fas fa-shopping-cart" style="color:white"><span>(<?= $total_cart_items; ?>)</span></div>
         <div id="order-btn" class="fas fa-box" style="color:white"></div>   
         <div id="user-btn" class="fas fa-user" style="color:white"></div>
      </div>

   </section>

</header>

<!-- HEADER -->

<div class="user-account">

   <section>

      <div id="close-account"><span> x </span></div>

      <div class="user">
         <?php
            $select_user = $conn->prepare("SELECT * FROM `user` WHERE id = ?");
            $select_user->execute([$user_id]);
            if($select_user->rowCount() > 0){
               while($fetch_user = $select_user->fetch(PDO::FETCH_ASSOC)){
                  echo '<p>¡ Bienvenido ! <span>'.$fetch_user['name'].'</span></p>';
                  echo '<a href="index.php?logout" class="btn">logout</a>';
               }
            }else{
               echo '<p><span>No has iniciado sesión</span></p>';
            }
         ?>
      </div>

      <div class="display-orders">
         <?php
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $select_cart->execute([$user_id]);
            if($select_cart->rowCount() > 0){
               while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
                  echo '<p>'.$fetch_cart['name'].' <span>('.$fetch_cart['price'].' x '.$fetch_cart['quantity'].')</span></p>';
               }
            }else{
               echo '<p><span>¡ Tu carrito está vacío !</span></p>';
            }
         ?>
      </div>

      <div class="flex">

         <form action="user_login.php" method="post">
            <h3>Inicia Sesión</h3>
            <input type="email" name="email" required class="box" placeholder="Correo Electrónico" maxlength="50">
            <input type="password" name="pass" required class="box" placeholder="Contraseña" maxlength="20">
            <input type="submit" value="Iniciar Sesión" name="login" class="btn">
         </form>

         <form action="" method="post">
            <h3>Registrate Ahora</h3>
            <input type="text" name="name" oninput="this.value = this.value.replace(/\s/g, '')" required class="box" placeholder="Ingrese su nombre de usuario" maxlength="20">
            <input type="email" name="email" required class="box" placeholder="Ingrese su correo electrónico" maxlength="50">
            <input type="password" name="pass" required class="box" placeholder="Ingrese su contraseña" maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
            <input type="password" name="cpass" required class="box" placeholder="Ingrese su contraseña" maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
            <input type="submit" value="Registrarse" name="register" class="btn">
         </form>

      </div>

   </section>

</div>

<div class="my-orders">

   <section>

      <div id="close-orders"><span> x </span></div>

      <h3 class="title"> Mis Pedidos </h3>

      <?php
         $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ?");
         $select_orders->execute([$user_id]);
         if($select_orders->rowCount() > 0){
            while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)){   
      ?>
      <div class="box">
         <p> Fecha : <span><?= $fetch_orders['placed_on']; ?></span> </p>
         <p> Nombre : <span><?= $fetch_orders['name']; ?></span> </p>
         <p> Teléfono : <span><?= $fetch_orders['number']; ?></span> </p>
         <p> Dirección : <span><?= $fetch_orders['address']; ?></span> </p>
         <p> Método de Pago : <span><?= $fetch_orders['method']; ?></span> </p>
         <p> Orden : <span><?= $fetch_orders['total_products']; ?></span> </p>
         <p> Precio Total : <span>$<?= $fetch_orders['total_price']; ?>/-</span> </p>
         <p> Estado de Pago: <span style="color:<?php if($fetch_orders['payment_status'] == 'pendiente'){ echo 'red'; }else{ echo 'green'; }; ?>"><?= $fetch_orders['payment_status']; ?></span> </p>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">No existe pedidos</p>';
      }
      ?>

   </section>

</div>

<div class="shopping-cart">

   <section>

      <div id="close-cart"><span> x </span></div>

      <?php
         $grand_total = 0;
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         if($select_cart->rowCount() > 0){
            while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
              $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
              $grand_total += $sub_total; 
      ?>
      <div class="box">
         <a href="index.php?delete_cart_item=<?= $fetch_cart['id']; ?>" class="fas fa-times" onclick="return confirm('¿Eliminar este artículo del carrito?');"></a>
         <img src="uploaded_img/<?= $fetch_cart['image']; ?>" alt="">
         <div class="content">
          <p> <?= $fetch_cart['name']; ?> <span>(<?= $fetch_cart['price']; ?> x <?= $fetch_cart['quantity']; ?>)</span></p>
          <form action="" method="post">
             <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
             <input type="number" name="qty" class="qty" min="1" max="99" value="<?= $fetch_cart['quantity']; ?>" onkeypress="if(this.value.length == 2) return false;">
               <button type="submit" class="fas fa-edit" name="update_qty"></button>
          </form>
         </div>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty"><span>Su carrito está vacío</span></p>';
      }
      ?>

      <div class="cart-total"> Total : <span>$<?= $grand_total; ?>/-</span></div>

      <a href="#order" class="btn">Realizar Pedido</a>

   </section>

</div>

<div class="home-bg">

   <section class="home" id="home">

      <div class="slide-container">

         <div class="slide active">
            <div class="image">
               <img src="images/muñecas_artesanales.png" alt="">
            </div>
            <div class="content">
               <h3 style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Muñecas Artesanales</h3>
               <div class="fas fa-angle-left" onclick="prev()"></div>
               <div class="fas fa-angle-right" onclick="next()"></div>
            </div>
         </div>

         <div class="slide">
            <div class="image">
               <img src="images/joyas_artesanales.png" alt="">
            </div>
            <div class="content">
               <h3 style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Joyas Artesanales</h3>
               <div class="fas fa-angle-left" onclick="prev()"></div>
               <div class="fas fa-angle-right" onclick="next()"></div>
            </div>
         </div>

         <div class="slide">
            <div class="image">
               <img src="images/juguetes_artesanales.png" alt="">
            </div>
            <div class="content">
               <h3 style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);">Juguetes Artesanales</h3>
               <div class="fas fa-angle-left" onclick="prev()"></div>
               <div class="fas fa-angle-right" onclick="next()"></div>
            </div>
         </div>

      </div>

   </section>

</div>

<!-- MENÚ -->

<section id="menu" class="menu" style="margin-top:3rem">

   <h1 class="heading">Nuestras Artesanías</h1>

   <div class="box-container">

      <?php
         $select_products = $conn->prepare("SELECT * FROM `products`");
         $select_products->execute();
         if($select_products->rowCount() > 0){
            while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){    
      ?>
      <div class="box">
         <div class="price">$<?= $fetch_products['price'] ?>/-</div>
         <img src="uploaded_img/<?= $fetch_products['image'] ?>" alt="">
         <div class="name"><?= $fetch_products['name'] ?></div>
         <form action="" method="post">
            <input type="hidden" name="pid" value="<?= $fetch_products['id'] ?>">
            <input type="hidden" name="name" value="<?= $fetch_products['name'] ?>">
            <input type="hidden" name="price" value="<?= $fetch_products['price'] ?>">
            <input type="hidden" name="image" value="<?= $fetch_products['image'] ?>">
            <input type="number" name="qty" class="qty" min="1" max="99" onkeypress="if(this.value.length == 2) return false;" value="1">
            <input type="submit" class="btn" name="add_to_cart" value="Agregar al Carrito">
         </form>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">¡Aún no se han añadido productos!</p>';
      }
      ?>

   </div>

</section>

<!-- MENÚ -->

<!-- HACER PEDIDO  -->

<section class="order" id="order" style="margin-top:3rem">

   <h1 class="heading">Ordenar Ahora</h1>

   <form action="" method="post">

   <div class="display-orders">

   <?php
         $grand_total = 0;
         $cart_item[] = '';
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         if($select_cart->rowCount() > 0){
            while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
              $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
              $grand_total += $sub_total; 
              $cart_item[] = $fetch_cart['name'].' ( '.$fetch_cart['price'].' x '.$fetch_cart['quantity'].' ) - ';
              $total_products = implode($cart_item);
              echo '<p>'.$fetch_cart['name'].' <span>('.$fetch_cart['price'].' x '.$fetch_cart['quantity'].')</span></p>';
            }
         }else{
            echo '<p class="empty"><span>¡Tu carrito está vacio!</span></p>';
         }
      ?>

   </div>

      <div class="grand-total"> Total : <span>$<?= $grand_total; ?>/-</span></div>

      <input type="hidden" name="total_products" value="<?= $total_products; ?>">
      <input type="hidden" name="total_price" value="<?= $grand_total; ?>">

      <div class="flex">
         <div class="inputBox">
            <span>Nombre :</span>
            <input type="text" name="name" class="box" required placeholder="" maxlength="20">
         </div>
         <div class="inputBox">
            <span>Número Telefónico :</span>
            <input type="number" name="number" class="box" required placeholder="" min="900000000" max="9999999999" onkeypress="if(this.value.length == 10) return false;">
         </div>
         <div class="inputBox">
            <span>Método de Pago</span>
            <select name="method" class="box">
               <option value="cash on delivery">Contra Reembolso</option>
               <option value="credit card">Tarjeta de crédito</option>
            </select>
         </div>
         <div class="inputBox">
            <span>Línea de dirección 01: </span>
            <input type="text" name="flat" class="box" required placeholder="" maxlength="50">
         </div>
         <div class="inputBox">
            <span>Línea de dirección 02 :</span>
            <input type="text" name="street" class="box" required placeholder="" maxlength="50">
         </div>
         <div class="inputBox">
            <span>Código (PIN) :</span>
            <input type="number" name="pin_code" class="box" required placeholder="" min="100" max="999" onkeypress="if(this.value.length == 3) return false;">
         </div>
      </div>

      <input type="submit" value="Ordenar ahora" class="btn" name="order">

   </form>

</section>

<!-- HACER PEDIDO-->

<script src="bot/chatbot.js"></script>

<!-- FOOTER  -->

<section class="footer">

   <div class="box-container">

      <div class="box">
         <i class="fas fa-clock"></i>
         <h3>Horario de Atención</h3>
         <p>L - V: 08:00 AM a 20:00 PM</p>
         <p>S - D: 10:00 AM a 14:00 PM</p>
      </div>

      <div class="box">
         <i class="fas fa-phone"></i>
         <h3>Números Telefónicos</h3>
         <p>948 789 859</p>
         <p>925 527 820</p>
      </div>

      <div class="box">
         <i class="fas fa-map-marker-alt"></i>
         <h3>Ubicación</h3>
         <p>Los Sauces Norte, calle 6 - 482</p>
         <p>Los Sauces Sur, calle 2 - 192</p>
      </div>

   </div>

   <div class="credit">
      &copy; 2023 - <span> Grupo 12  Cloud Computing </span>
   </div>

</section>

<!-- FOOTER -->





<!-- custom js file link  -->
<script src="js/script.js"></script>

</body>
</html>