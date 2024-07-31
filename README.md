# back_prolipa
Backtend de la Plataforma Prolipa

/vendor/laravel/framework/src/Illuminate/Auth/EloquentUserProvider.php

    public function validateCredentials(UserContract $user, array $credentials)
    {
        // $plain = $credentials['password'];

        // return $this->hasher->check($plain, $user->getAuthPassword());
        $plain = $credentials['password'];
        $hashed_value = $user->getAuthPassword();
        return $hashed_value == sha1(md5($plain));
    }

/vendor/laravel/framework/src/Illuminate/Foundation/Auth/AuthenticatesUsers.php

    public function username()
    {
        return 'name_usuario';
    }
