<?= $this->extend('exAuth\layout') ?>

<?= $this->section('title') ?>Forgot Password<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-12 col-md-5 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Forgot Password</h5>

            <?php if (session('error') !== null) : ?>
                <div class="alert alert-danger"><?= esc(session('error')) ?></div>
            <?php endif ?>
            <?php if (session('message') !== null) : ?>
                <div class="alert alert-success"><?= esc(session('message')) ?></div>
            <?php endif ?>

            <form action="<?= url_to('forgot-password') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary">Send Reset Link</button>
                </div>

                <p class="text-center"><a href="<?= url_to('login') ?>">Back to login</a></p>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
