<?php
/**
 * Created by PhpStorm.
 * User: mirceasoaica
 * Date: 03/06/2018
 * Time: 11:38
 */

namespace EloquentElastic\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;

class HasMany extends EloquentHasMany
{

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            // TODO: Implement HasMany constraints
            dd($this->getParentKey());
            $this->query->where($this->foreignKey, '=', $this->getParentKey());
        }
    }
}
