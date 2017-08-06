<form class="form-horizontal" method="POST" action="<?= $this->getUrl(['action' => 'save']) ?>">
    <legend><?=$this->getTrans('twitterauth.settings') ?></legend>
    <div class="alert alert-info">
        <?= $this->getTrans('twitterauth.getyourkeys', '<a href="https://apps.twitter.com/" target="_blank">https://apps.twitter.com/</a>') ?>
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
    <?=$this->getSaveBar() ?>
</form>
