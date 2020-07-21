<form class="form-horizontal" method="POST" action="<?= $this->getUrl(['action' => 'save']) ?>">
    <legend><?=$this->getTrans('twitterauth.settings') ?></legend>
    <div class="alert alert-info">
        <?= $this->getTrans('twitterauth.getyourkeys', '<a href="https://developer.twitter.com/apps" target="_blank">https://developer.twitter.com/apps</a>') ?>
    </div>
    <?=$this->getTokenField() ?>
    <div class="form-group">
        <label for="consumerKeyInput" class="col-lg-2 control-label">
            <?=$this->getTrans('twitterauth.consumerkey') ?>:
        </label>
        <div class="col-lg-10">
            <input type="text"
                   class="form-control"
                   id="consumerKeyInput"
                   name="consumerKey"
                   value="<?=$this->escape($this->get('twitterauth')['consumerKey']) ?>" />
        </div>
    </div>
    <div class="form-group">
        <label for="consumerSecretInput" class="col-lg-2 control-label">
            <?=$this->getTrans('twitterauth.consumersecret') ?>:
        </label>
        <div class="col-lg-10">
            <input type="password"
                   class="form-control"
                   id="consumerSecretInput"
                   name="consumerSecret"
                   value="<?= $this->escape($this->get('twitterauth')['consumerSecret']); ?>" />
        </div>
    </div>
    <div class="form-group">
        <label for="accessTokenInput" class="col-lg-2 control-label">
            <?=$this->getTrans('twitterauth.accesstoken') ?>:
        </label>
        <div class="col-lg-10">
            <input type="text"
                   class="form-control"
                   id="accessTokenInput"
                   name="accessToken"
                   value="<?=$this->escape($this->get('twitterauth')['accessToken']) ?>" />
        </div>
    </div>
    <div class="form-group">
        <label for="accessTokenSecretInput" class="col-lg-2 control-label">
            <?=$this->getTrans('twitterauth.accesstokensecret') ?>:
        </label>
        <div class="col-lg-10">
            <input type="password"
                   class="form-control"
                   id="accessTokenSecretInput"
                   name="accessTokenSecret"
                   value="<?= $this->escape($this->get('twitterauth')['accessTokenSecret']); ?>" />
        </div>
    </div>
    <div class="form-group">
        <label for="" class="col-lg-2 control-label">
            <?=$this->getTrans('twitterauth.callbackUrl') ?>:
        </label>
        <div class="col-lg-10">
            <p class="form-control-static"><?= $this->escape($this->get('callbackUrl')) ?></p>
        </div>
    </div>
    <div class="form-group">
        <label for="debugging" class="col-lg-2 control-label">
            <?=$this->getTrans('twitterauth.debugging') ?>:
        </label>
        <div class="col-lg-10">
            <div class="radio">
                <label>
                  <input type="radio" name="debugging" id="activateDebugging" value="1" <?= $this->get('debugging') === '1' ? 'checked' : '' ?>>
                  aktiviert
                </label>
            </div>
            <div class="radio">
                <label>
                  <input type="radio" name="debugging" id="deactivateDebugging" value="0" <?= $this->get('debugging') === '0' ? 'checked' : '' ?>>
                  deaktiviert
                </label>
            </div>
            <span class="help-block">Debugging sollte nur für Entwicklungszwecke aktiviert werden, da es möglicherweise sensible Daten in der Datenbank speichert.</span>
        </div>
    </div>
    <?=$this->getSaveBar() ?>
</form>
