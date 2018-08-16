<?php

namespace traits\model;

use think\db\Query;
use think\Model;

/**
 * @mixin \Think\Model
 */
trait BoolSoftDelete
{
    /**
     * 判断当前实例是否被软删除.
     *
     * @return bool
     */
    public function trashed()
    {
        $field = $this->getSoftDeleteField();

        if ($field && !empty($this->data[$field])) {
            return true;
        }

        return false;
    }

    /**
     * 查询包含软删除的数据.
     *
     * @return Query
     */
    public static function withTrashed()
    {
        return (new static() )->getQuery();
    }

    /**
     * 只查询软删除数据.
     *
     * @return Query
     */
    public static function onlyTrashed()
    {
        $model = new static();
        $field = $model->getSoftDeleteField(true);

        if ($field) {
            return $model->getQuery()->useSoftDelete($field, 1);
        } else {
            return $model->getQuery();
        }
    }

    /**
     * 删除当前的记录.
     *
     * @param bool $force 是否强制删除
     *
     * @return int
     */
    public function delete($force = false)
    {
        if (false === $this->trigger('before_delete', $this)) {
            return false;
        }

        $name = $this->getSoftDeleteField();
        $field_deletetime = $this->getDeleteTimeField();
        if ($name && !$force) {
            // 软删除
            $this->data[$name] = 1;
            // 在5.0中autoWriteTimestamp会有bug
//            $this->data[$field_deletetime] = $this->autoWriteTimestamp($field_deletetime);
            $this->data[$field_deletetime] = date('Y-m-d H:i:s', time());
            $result = $this->isUpdate()->save();
        } else {
            // 强制删除当前模型数据
            $result = $this->getQuery()->where($this->getWhere())->delete();
        }

        // 关联删除
        if (!empty($this->relationWrite)) {
            foreach ($this->relationWrite as $key => $name) {
                $name = is_numeric($key) ? $name : $key;
                $result = $this->getRelation($name);
                if ($result instanceof Model) {
                    $result->delete();
                } elseif ($result instanceof Collection || is_array($result)) {
                    foreach ($result as $model) {
                        $model->delete();
                    }
                }
            }
        }

        $this->trigger('after_delete', $this);

        // 清空原始数据
        $this->origin = [];

        return $result;
    }

    /**
     * 删除记录.
     *
     * @param mixed $data  主键列表(支持闭包查询条件)
     * @param bool  $force 是否强制删除
     *
     * @return int 成功删除的记录数
     */
    public static function destroy($data, $force = false)
    {
        if (is_null($data)) {
            return 0;
        }

        // 包含软删除数据
        $query = self::withTrashed();
        if (is_array($data) && key($data) !== 0) {
            $query->where($data);
            $data = null;
        } elseif ($data instanceof \Closure) {
            call_user_func_array($data, [&$query]);
            $data = null;
        }

        $count = 0;
        if ($resultSet = $query->select($data)) {
            foreach ($resultSet as $data) {
                $result = $data->delete($force);
                $count += $result;
            }
        }

        return $count;
    }

    /**
     * 恢复被软删除的记录.
     *
     * @param array $where 更新条件
     *
     * @return int
     */
    public function restore($where = [])
    {
        if (empty($where)) {
            $pk = $this->getPk();
            $where[$pk] = $this->getData($pk);
        }

        $name = $this->getSoftDeleteField();

        if ($name) {
            // 恢复删除
            return $this->getQuery()
                ->useSoftDelete($name, 1)
                ->where($where)
                ->update([$name => 0]);
        } else {
            return 0;
        }
    }

    /**
     * 查询默认不包含软删除数据.
     *
     * @param Query $query 查询对象
     *
     * @return Query
     */
    protected function base($query)
    {
        $field = $this->getSoftDeleteField(true);

        return $field ? $query->useSoftDelete($field, 0) : $query;
    }

    /**
     * 获取软删除时间字段.
     *
     * @param bool $read 是否查询操作(写操作的时候会自动去掉表别名)
     *
     * @return string
     */
    protected function getDeleteTimeField($read = false)
    {
        $field = property_exists($this, 'deleteTime') && isset($this->deleteTime) ?
            $this->deleteTime :
            'delete_time';

        if (false === $field) {
            return false;
        }

        if (!strpos($field, '.')) {
            $field = '__TABLE__.'.$field;
        }

        if (!$read && strpos($field, '.')) {
            $array = explode('.', $field);
            $field = array_pop($array);
        }

        return $field;
    }

    /**
     * 获取软删除字段.
     *
     * @param bool $read 是否查询操作(写操作的时候会自动去掉表别名)
     *
     * @return string
     */
    protected function getSoftDeleteField($read = false)
    {
        $field = property_exists($this, 'softDeleteField') && isset($this->softDeleteField) ?
        $this->softDeleteField :
        'is_delete';

        if (false === $field) {
            return false;
        }

        if (!strpos($field, '.')) {
            $field = '__TABLE__.'.$field;
        }

        if (!$read && strpos($field, '.')) {
            $array = explode('.', $field);
            $field = array_pop($array);
        }

        return $field;
    }
}
