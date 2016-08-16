<link href="<?=$this->getModuleUrl('static/css/twitter.css') ?>" rel="stylesheet">

<form class="form-horizontal" method="POST" action="<?= $this->getUrl(['action' => 'save']) ?>" autocomplete="off">
    <legend><i class="fa fa-twitter twitterBlue"></i> <?=$this->getTrans('twitter_auth') ?></legend>
    <div class="panel panel-default">
        <div class="bg-info panel-body">
            <?= $this->getTrans('add_a_password_and_email') ?>
        </div>
        <div class="panel-body">
          <div class="media">
            <div class="media-left">
              <a href="#">
                <img class="media-object" src="<?= $this->get('user')->profile_image_url_https ?>" alt="Profile picture <?= $this->get('user')->screen_name ?>">
              </a>
            </div>
            <div class="media-body">
              <h4 class="media-heading"><?= $this->get('user')->screen_name ?></h4>
              <?= $this->get('user')->description; ?>
            </div>
          </div>
        </div>
        <div class="panel-body">
          <?php if ($this->get('errors')->hasErrors()): ?>
              <div class="alert alert-danger" role="alert">
                  <strong> <?=$this->getTrans('errorsOccured') ?>:</strong>
                  <ul>
                      <?php foreach ($this->get('errors')->getErrorMessages() as $error): ?>
                          <li><?= $error; ?></li>
                      <?php endforeach; ?>
                  </ul>
              </div>
          <?php endif; ?>
            <?=$this->getTokenField() ?>
            <div class="form-group <?= ! $this->get('errors')->hasError('userName') ?: 'has-error' ?>">
                <label for="userNameInput" class="col-lg-3 control-label">
                    <?=$this->getTrans('userName') ?>:
                </label>
                <div class="col-lg-9">
                    <input type="text"
                           class="form-control"
                           id="userNameInput"
                           name="userName"
                           value="<?= array_dot($this->get('old'), 'userName') ? array_dot($this->get('old'), 'userName') : $this->get('user')->screen_name ?>" />
                </div>
            </div>
            <div class="form-group <?= ! $this->get('errors')->hasError('email') ?: 'has-error' ?>">
                <label for="emailInput" class="col-lg-3 control-label">
                    <?=$this->getTrans('email') ?>:
                </label>
                <div class="col-lg-9">
                    <input type="email"
                           class="form-control"
                           id="emailInput"
                           name="email"
                           value="<?= array_dot($this->get('old'), 'email', '') ?>" />
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-arrow-right"></i> <?= $this->getTrans('complete_registration') ?></button>
            <a href="#" class="btn btn-default"><?= $this->getTrans('cancel') ?></a>
        </div>
    </div>
</form>
