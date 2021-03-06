<?php 
require_once 'core.php';

$idx = isset($_GET['idx']) ? 1 : 0;
$title = $clientJson['calendarName'][$idx];
$apiKey = $clientJson['api'];
$url = $clientJson['web']['redirect_uris'][0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title><?=$title?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <!-- Include Google Maps Api -->
  <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?=$apiKey?>&libraries=places"></script>
  <!-- Include Project Libs -->
  <script type="text/javascript" src="index.js"></script>
  <link rel="stylesheet" href="index.css"/>
  <link rel="shortcut icon" href="favicon.png">
</head>
<body>

<div class="container">
  <a href="<?=$url?>" target="_parent"><img src="logo.png"></a>
  <h2><?=$title?></h2>
  <form id="calendar_send" method="post" action="send_event.php">
    <div class="form-group">
      <label for="address">
       Endere&ccedil;o <span class="asteriskField">*</span>
      </label>
       <input class="form-control" id="address" name="address" placeholder="Procure um endere&ccedil;o" type="text"/>
    </div>

    <div class="form-group">
      <label for="predio">
       Nome do Pr&eacute;dio <span class="asteriskField">*</span>
      </label>
       <input class="form-control" id="predio" name="predio" type="text"/>
    </div>

    <div class="row">
      <div class="form-group col-sm-6">
        <label for="apto">
         N&uacute;mero do Apto <span class="asteriskField">*</span>
        </label>
       <input class="form-control" id="apto" name="apto" type="number"/>
      </div>
      <div class="form-group col-sm-6">
        <label for="torre">
         Torre <span class="asteriskField">*</span>
        </label>
       <input class="form-control" id="torre" name="torre" type="text"/>
      </div>
    </div>

    <div class="form-group">
      <label for="vaga">
       Vaga <span class="asteriskField">*</span>
      </label>
       <input class="form-control" id="vaga" name="vaga" type="text"/>
    </div>

    <div class="form-group">
      <label for="date">
       Data <span class="asteriskField">*</span>
      </label>
       <input class="form-control" id="date" name="date" placeholder="dd/mm/yyyy" type="date"/>
    </div>

    <div class="row">
      <div class="form-group col-sm-6">
        <label for="apto">
         Selecione a metragem <span class="asteriskField">*</span>
        </label>
         <select class="select form-control" id="metragem" name="metragem">
          <option value="">Selecione a metragem</option>
          <option value="100">at&eacute; 100m&sup2;</option>
          <option value="150">at&eacute; 150m&sup2;</option>
          <option value="200">at&eacute; 200m&sup2;</option>
          <option value="250">at&eacute; 250m&sup2;</option>
          <option value="300">at&eacute; 300m&sup2;</option>
          <option value="350">at&eacute; 350m&sup2;</option>
          <option value="400">at&eacute; 400m&sup2;</option>
          <option value="450">at&eacute; 450m&sup2;</option>
          <option value="500">at&eacute; 500m&sup2;</option>
          <option value="550">at&eacute; 550m&sup2;</option>
          <option value="600">at&eacute; 600m&sup2;</option>
          <option value="650">at&eacute; 650m&sup2;</option>
          <option value="700">at&eacute; 700m&sup2;</option>
          <option value="750">at&eacute; 750m&sup2;</option>
          <option value="800">at&eacute; 800m&sup2;</option>
          <option value="850">at&eacute; 850m&sup2;</option>
          <option value="900">at&eacute; 900m&sup2;</option>
          <option value="950">at&eacute; 950m&sup2;</option>
          <option value="1000">at&eacute; 1000m&sup2;</option>
         </select>
      </div>
      <div class="form-group col-sm-6">
        <label for="torre">
         Selecione a infraestrutura <span class="asteriskField">*</span>
        </label>
         <select class="select form-control" id="infra" name="infra">
          <option value="">Selecione a infraestrutura</option>
          <option value="0">sem infraestrutura</option>
          <option value="5">at&eacute; 5 ambientes</option>
          <option value="10">at&eacute; 10 ambientes</option>
          <option value="20">at&eacute; 20 ambientes</option>
          <option value="60">mais que 20 ambientes</option>
         </select>
      </div>
    </div>

    <div class="form-group">
      <div>
       <div class="radio-inline">
        <label class="checkbox">
         <input name="livre" type="checkbox" value="livre"/>
         Hor&aacute;rio livre (chave disponível)
        </label>
       </div>
      <div id="chave-div" style="display:none;">
        <label for="chave">
         Chave <span class="asteriskField">*</span>
        </label>
         <input class="form-control" id="chave" name="chave" type="text" disabled="true"/>
      </div>
      </div>
    </div>

    <div class="form-group horario-div">
       <button class="btn btn-primary" name="submit" type="button" id="checkAvaiable">
        Verificar Disponibilidade
       </button>
       <div id="loader"></div>
    </div>

    <div class="form-group horario-div">
      <label for="horario">
       Selecione um hor&aacute;rio <span class="asteriskField">*</span>
      </label>
       <select class="select form-control" id="horario" name="horario">
        <option value="">
         Verifique a disponibilidade
        </option>
       </select>
    </div>

    <div class="form-group">
      <label class="control-label" for="cliente">
       Respons&aacute;vel pelo Agendamento <span class="asteriskField">*</span>
      </label>
      <div>
       <div class="radio-inline">
        <label class="radio">
         <input name="cliente" type="radio" value="Corretor" checked="true"/>
         Imobili&aacute;ria
        </label>
       </div>
       <div class="radio-inline">
        <label class="radio">
         <input name="cliente" type="radio" value="Proprietario"/>
         Propriet&aacute;rio
        </label>
       </div>
      </div>
    </div>

    <div class="form-group">
      <label for="proprietario_nome">
       Nome do Propriet&aacute;rio e/ou Respons&aacute;vel<span class="asteriskField">*</span>
      </label>
       <input class="form-control" id="proprietario_nome" name="proprietario_nome" type="text"/>
    </div>

    <div class="form-group">
      <label for="proprietario_tel">
       Telefone do Propriet&aacute;rio e/ou Respons&aacute;vel<span class="asteriskField">*</span>
      </label>
       <input class="form-control" id="proprietario_tel" name="proprietario_tel" type="tel" placeholder="(##) 9.####.####"/>
    </div>

    <div id="corretor-div">

      <div class="form-group">
        <label for="imobiliaria">
         Imobiliária <span class="asteriskField">*</span>
        </label>
        <input class="form-control corretor-fields" id="imobiliaria" name="imobiliaria" type="text"/>
      </div>

      <div class="form-group">
        <label for="corretor_nome">
         Nome do Corretor <span class="asteriskField">*</span>
        </label>
         <input class="form-control corretor-fields" id="corretor_nome" name="corretor_nome" type="text"/>
      </div>

      <div class="form-group">
        <label for="corretor_tel">
         Telefone do Corretor <span class="asteriskField">*</span>
        </label>
         <input class="form-control corretor-fields" id="corretor_tel" name="corretor_tel" type="tel" placeholder="(##) 9.####.####"/>
      </div>

    </div>

    <div class="form-group">
      <label for="email">
       Email <span class="asteriskField">*</span>
      </label>
       <input class="form-control" id="email" name="email" type="text"/>
    </div>

    <div class="form-group">
      <label for="obsservacao">
       Observação
      </label>
       <textarea class="form-control" id="obsservacao" name="obsservacao" type="text" rows="4"> </textarea>
    </div>

    <input  id="idx" name="idx" type="hidden" value="<?=$idx?>"/>
    <input  id="tempo" name="tempo" type="hidden" value="44"/>
    <input  id="title" name="title" type="hidden" value=" "/>
    <button type="submit" name="submit" class="btn btn-primary">Enviar Agendamento</button>
  </form>
</div>

</body>
</html>