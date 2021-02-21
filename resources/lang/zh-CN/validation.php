<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'   => '您必须同意 :attribute。',
    'active_url' => ':attribute 不是一个有效的URL网址。',
    'after'      => ':attribute 必须在 :date 之后。',
    'alpha'      => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母，数字和破折号。',
    'alpha_num'  => ':attribute 只允许包含字母和数字。',
    'array'      => ':attribute 必须是个数组。',
    'before'     => ':attribute 必须在 :date 之前。',
    'between'    => [
        'numeric' => ':attribute 必须在 :date 之前。',
        'file'    => ':attribute 必须在 :min 和 :max 之间。',
        'string'  => ':attribute 必须在 :min KB 到 :max KB 之间。',
        'array'   => ':attribute 必须在 :min 到 :max 个数目之间。',
    ],
    'boolean'        => ':attribute 必须在 :min 到 :max 个数目之间。',
    'confirmed'      => ':attribute 字段必须为 true 或 false。',
    'date'           => ':attribute 的两次输入不匹配。',
    'date_format'    => ':attribute 不是有效的日期。',
    'different'      => ':attribute 不符合格式 :format 。',
    'digits'         => ':attribute 和 :other 不能相同。',
    'digits_between' => ':attribute 必须是 :digits 位数字。',
    'email'          => ':attribute 必须是 :min - :max 位数字。',
    'exists'         => ':attribute 必须是一个有效的电子邮件地址。',
    'distinct'       => ':attribute 有重复。',
    'filled'         => ':attribute 的格式无效',
    'image'          => ':attribute 必须是图片。',
    'in'             => ':attribute 必须是图片。',
    'in_array'       => ':attribute 不在 :other 中。',
    'integer'        => '选择的 :attribute 无效。',
    'ip'             => ':attribute 必须是整数。',
    'json'           => ':attribute 必须是规范的 JSON 字串。',
    'max'            => [
        'numeric' => ':attribute 必须是一个有效的 IP 地址。',
        'file'    => ':attribute 不允许大于 :max。',
        'string'  => ':attribute 不能大于 :max KB。',
        'array'   => ':attribute 不能超过 :max 个。',
    ],
    'mimes' => ':attribute 不能超过 :max 个。',
    'min'   => [
        'numeric' => ':attribute 文件类型必须是 :values。',
        'file'    => ':attribute 至少需要 :min KB。',
        'string'  => ':attribute 至少需要 :min KB。',
        'array'   => ':attribute 最少需要有 :min 个字符。',
    ],
    'not_in'               => ':attribute 至少需要有 :min 项。',
    'numeric'              => '选择的 :attribute 无效。',
    'present'              => ':attribute 为必填项。',
    'regex'                => ':attribute 必须是数字。',
    'required'             => ':attribute 的格式无效',
    'required_if'          => ':attribute 字段必填。',
    'required_unless'      => ':attribute 是必须的除非 :other 在 :values 中。',
    'required_with'        => ':attribute 字段在 :other 是 :value 时是必填的。',
    'required_with_all'    => '当含有 :values 时， :attribute 是必需的。',
    'required_without'     => '当含有 :values 时， :attribute 是必需的。',
    'required_without_all' => '当 :values 不存在时， :attribute 是必填的。',
    'same'                 => '当没有任何 :values 存在时，:attribute 字段为必填项。',
    'size'                 => [
        'numeric' => ':attribute 和 :other 必须匹配。',
        'file'    => ':attribute 必须是 :size KB大小',
        'string'  => ':attribute 必须是 :size 个字符',
        'array'   => ':attribute 必须是 :size 个字符',
    ],
    'string'   => ':attribute 必须包含 :size 个项。',
    'timezone' => ':attribute 必须是个有效的区域。',
    'unique'   => ':attribute 已经被占用',
    'url'      => ':attribute 的格式无效',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => '自定义消息',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
