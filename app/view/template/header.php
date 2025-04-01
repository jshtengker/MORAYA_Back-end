<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF=8">
        <title><?php echo $data['title']; ?></title>

        <link href="<?php echo APP_PATH;?>/css/bootstrap.min.css" rel="stylesheet">
    </head>
   <body>
   <nav class="navbar navbar-expand-lg navbar-light bg-light mt-0 pt-0">
  <div class="container-fluid mt-0 pt-0">
    <a class="navbar-brand" href="<?php echo APP_PATH; ?>">Navbar</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="<?php echo APP_PATH; ?>">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo APP_PATH; ?>/home/page/">Page</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo APP_PATH; ?>/project">Pricing</a>
        </li>
        <li class="nav-item">
          <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
