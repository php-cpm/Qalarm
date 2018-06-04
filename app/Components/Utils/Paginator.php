<?php
namespace App\Components\Utils;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;

class Paginator
{
    /**
     * LastId的字段必须保证：唯一，可排序
     */
    const MODE_LASTID = 1;

    const MODE_INDEX  = 2;

    // Default condition field
    protected $column = 'id';

    // Default operator
    protected $operator = '>';

    // Default page size
    protected $defaultSize = 15;

    // Pagination mode
    protected $mode;

    // Request page size
    protected $size;

    // Request page from
    protected $from;

    // Request page index
    protected $index;

    // Request status of more
    protected $hasMore;

    // Collection count()
    protected $total;

    public function __construct($options)
    {
        if ($options instanceof Request) {
            $this->setParamByRequest($options);
        } else {
            throw new RuntimeException('Invalid option');
        }
    }

    /**
     * 根据请求设置查询参数
     */
    public function setParamByRequest(Request $request)
    {
        if ($request->has('page_from')) {
            $this->mode = self::MODE_LASTID;
            $this->from = $request->input('page_from');
        } else {
            $this->mode = self::MODE_INDEX;
            $this->index = $request->input('page_index', 1);
        }
        $this->size = $request->input('page_size', $this->defaultSize);
    }

    /**
     * 分页执行查询
     */
    public function runQuery(Builder $query)
    {
        $this->total = $query->count();

        if ($this->mode == self::MODE_LASTID) {
            $query->skip($this->from);
            /**
             * FIXME 临时改为limit的分页
             * $query->where($this->column, $this->operator, $this->from);
             */
        } else {
            $query->skip($this->size * ($this->index - 1));
        }

        $this->collection = $query->take($this->size + 1)->get();

        // Check has more
        if ($this->collection->count() == $this->size + 1) {
            $this->hasMore = 1;
            $this->collection->pop();
        } else {
            $this->hasMore = 0;
        }

        return $this->collection;
    }

    /**
     * Get collection stats info.
     *
     * @return array
     */
    public function info(Collection $collection = null, $has_more = null)
    {
        if (is_null($collection)) {
            $collection = $this->collection;
        }
        if (is_null($has_more)) {
            $has_more = $this->hasMore;
        }

        $info = [
            'size'  => $this->size,
            'count' => $collection->count(),
            'total' => $this->total,
            'has_more' => $has_more ? 1 : 0, // 限制返回数据为int
        ];

        if (!$collection->isEmpty()) {
            $info['from'] = $this->from + 1;
            $info['to'] = $this->from + $collection->count();
            /**
             * FIXME 临时改为limit的分页
             * $info['from'] = $collection->first()->{$this->column};
             * $info['to'] = $collection->last()->{$this->column};
             */
        }

        if ($this->mode == self::MODE_INDEX) {
            $info['index'] = $this->index;
        }

        return $info;
    }
}
