<?php

namespace App\Enums;

enum ApiKeyRole: string
{
    case Admin = "admin";
    case User = "user";
}
