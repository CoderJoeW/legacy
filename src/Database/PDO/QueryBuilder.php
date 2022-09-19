<?php
namespace HE\Database\PDO;

class QueryBuilder{
    /** @var array<string> $fields */
    private array $fields = [];

    /** @var array<string> $whereConditions */
    private $whereConditions = [];

    /** @var array<string> $havingConditions */
    private $havingConditions = [];

    /** @var array<string> $fromConditions */
    private $fromConditions = [];

    /** @var array<string> $joinConditions */
    private $joinConditions = [];

    /** @var array<string> $order */
    private $order = [];

    /** @var string $group */
    private $group;

    /** @var int|string $limitCondition */
    private $limitCondition;

    /** @var int|string $offsetCondition */
    private $offsetCondition;

    /** @var string $beforeCondition */
    private $beforeCondition;

    public function __toString(): string
    {
        $before = $this->beforeCondition;
        $select = 'SELECT ' . implode(', ', $this->fields);
        $from = ' FROM ' . implode(', ', $this->fromConditions);
        $join = ($this->joinConditions === [] ? '' :  implode(' ', $this->joinConditions));
        $where = ($this->whereConditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->whereConditions));
        $having = ($this->havingConditions === [] ? '' : ' HAVING ' . implode(' AND ', $this->havingConditions));
        $order = ($this->order === [] ? '' : ' ORDER BY ' . implode(', ', $this->order));
        $group = ($this->group === null ? '' : ' GROUP BY ' . $this->group);
        $limit = ($this->limitCondition === null ? '' : ' LIMIT ' . $this->limitCondition);
        $offset= ($this->offsetCondition === null || $this->offsetCondition === 0 ? '' : ' OFFSET ' . $this->offsetCondition);

        return "$before $select $from $join $where $having $group $order $limit $offset";
    }

    public function pagination(){
        $from = ' FROM ' . implode(', ', $this->fromConditions);
        $where = ($this->whereConditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->whereConditions));
        $having = ($this->havingConditions === [] ? '' : ' HAVING ' . implode(' AND ', $this->havingConditions));
        $limit = $this->limitCondition;

        $paginationQuery = "
            SELECT expr2.id
            FROM ( 
                SELECT id,row_num,(expr1.row_num % $limit) as row_mod
                FROM ( 
                    SELECT id, ROW_NUMBER() OVER(ORDER BY id DESC) AS row_num
                    $from
                    $where
                    $having
                ) as expr1
            ) as expr2
            WHERE expr2.row_mod = 0    
        ";

        return $paginationQuery;
    }

    public function resultCount(){
        $from = ' FROM ' . implode(', ', $this->fromConditions);
        $where = ($this->whereConditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->whereConditions));
        $having = ($this->havingConditions === [] ? '' : ' HAVING ' . implode(' AND ', $this->havingConditions));

        $resultCountQuery = "
            SELECT Count(*) as rowCount
            $from
            $where
            $having
        ";

        return $resultCountQuery;
    }

    public function before(string $before): self
    {
        $this->beforeCondition = $before;
        return $this;
    }

    public function select(string ...$select): self
    {
        foreach($select as $arg){
            $this->fields[] = $arg;
        }
        return $this;
    }

    public function where(string ...$where): self
    {
        foreach ($where as $arg) {
            $this->whereConditions[] = $arg;
        }
        return $this;
    }

    public function having(string ...$having): self
    {
        foreach ($having as $arg) {
            $this->havingConditions[] = $arg;
        }
        return $this;
    }

    public function from(string $table, ?string $alias = null): self
    {
        if ($alias === null) {
            $this->fromConditions[] = $table;
        } else {
            $this->fromConditions[] = "${table} AS ${alias}";
        }
        return $this;
    }

    public function join(string $table, string $on, string $type): self
    {
        $this->joinConditions[] = "$type JOIN $table ON $on";
        return $this;
    }

    public function orderBy(string $sort, string $order = 'ASC'): self
    {
        $this->order[] = "$sort $order";
        return $this;
    }

    public function groupBy(string $group): self
    {
        $this->group= $group;
        return $this;
    }

    public function limit(int|string $limit): self
    {
        $this->limitCondition = $limit;
        return $this;
    }

    public function offset(int|string $offset): self
    {
        $this->offsetCondition = $offset;
        return $this;
    }
}
?>
