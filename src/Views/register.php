<?= $this->extend('exAuth\layout') ?>

<?= $this->section('title') ?>Register<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="container d-flex justify-content-center p-5">
    <div class="card col-12 col-md-5 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Register</h5>

            <?php if (session('error') !== null) : ?>
                <div class="alert alert-danger"><?= esc(session('error')) ?></div>
            <?php endif ?>
            <?php if (session('errors') !== null && is_array(session('errors'))) : ?>
                <div class="alert alert-danger">
                    <?php foreach (session('errors') as $error) : ?>
                        <?= esc($error) ?><br>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <form action="<?= url_to('register') ?>" method="post">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" inputmode="email" autocomplete="email" value="<?= old('email') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" autocomplete="username" value="<?= old('username') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirm">Confirm Password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>

                <p class="text-center">Already have an account? <a href="<?= url_to('login') ?>">Login</a></p>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
