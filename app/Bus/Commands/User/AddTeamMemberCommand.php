<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Bus\Commands\User;

final class AddTeamMemberCommand
{
    /**
     * The user email.
     *
     * @var string
     */
    public $email;

    /**
     * The user level.
     *
     * @var int
     */
    public $level;

    /**
     * The validation rules.
     *
     * @var string[]
     */
    public $rules = [
        'email'    => 'required|email',
        'level'    => 'int',
    ];

    /**
     * Create a new add team member command instance.
     *
     * @param string $email
     * @param int    $level
     *
     * @return void
     */
    public function __construct($email, $level)
    {
        $this->email = $email;
        $this->level = $level;
    }
}
