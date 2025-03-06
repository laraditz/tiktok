<?php

namespace Laraditz\TikTok\Enums;

enum UserType: int
{
    case Seller = 0;
    case Creator = 1;
    case Partner = 3;
}
