<?php

return [

    'admin_cannot_modify' => 'Las cuentas de administrador no pueden desactivarse ni eliminarse.',

    'deactivated' => 'Cuenta desactivada.',

    'activated' => 'Cuenta activada.',

    'no_scheduled_deletion' => 'No hay eliminación programada para esta cuenta.',

    'deletion_scheduled' => 'Tu cuenta se eliminará de forma permanente en :days días. Tras la eliminación permanente ya no podrás acceder a esta cuenta. Puedes restaurar o cancelar la eliminación durante este periodo de :days días.',

    'deletion_restore_login' => 'Tu cuenta está programada para eliminación. Puedes restaurarla antes de que termine el periodo de gracia.',

    'deletion_processing' => 'La eliminación de tu cuenta se está finalizando. Ya no puedes restaurar esta cuenta.',

    'permanently_deleted' => 'Tu cuenta fue eliminada de forma permanente.',

    'suspended' => 'Tu cuenta ha sido suspendida. Ponte en contacto con soporte.',

    'restore_otp_sent' => 'Se ha enviado un código de verificación a tu correo electrónico.',

    'restore_otp_resend_wait' => 'Espera antes de solicitar un nuevo código de verificación.',

    'restore_success' => 'Se ha cancelado la eliminación de tu cuenta.',

    'restore_invalid_otp' => 'El código de verificación no es válido o ha caducado.',

    'password_reset_otp_resend_wait' => 'Espera antes de solicitar un nuevo código de restablecimiento de contraseña.',

    'password_reset_invalid_otp' => 'El código de restablecimiento de contraseña no es válido o ha caducado.',

    'email_verification_sent' => 'Se ha enviado un código de verificación a su correo electrónico.',

    'email_verification_resend_wait' => 'Espere antes de solicitar un nuevo código de verificación.',

    'email_verification_invalid_otp' => 'El código de verificación no es válido o ha caducado.',

    'email_verification_token_invalid' => 'La sesión de verificación no es válida o ha caducado.',

    'email_verification_already_verified' => 'Esta dirección de correo electrónico ya está verificada.',

    'password_changed' => 'Tu contraseña se ha cambiado correctamente.',

    'two_factor' => [
        'enabled' => 'Configuración de autenticación en dos pasos iniciada. Escanea el código QR y confirma con un código válido.',
        'confirmed' => 'La autenticación en dos pasos está activada.',
        'disabled' => 'La autenticación en dos pasos se ha desactivado.',
        'recovery_codes_regenerated' => 'Se han generado nuevos códigos de recuperación.',
        'invalid_challenge' => 'Sesión de dos factores no válida o caducada.',
        'invalid_code' => 'El código de autenticación en dos pasos no es válido.',
        'already_enabled' => 'La autenticación en dos pasos ya está activada.',
        'not_started' => 'La autenticación en dos pasos no se ha iniciado.',
        'not_enabled' => 'La autenticación en dos pasos no está activada.',
        'required_when_login' => 'Se requiere autenticación en dos pasos.',
    ],

];
