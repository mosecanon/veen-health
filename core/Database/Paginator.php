<?php
namespace Veen\Core\Database;

class Paginator implements \IteratorAggregate, \Countable
{
    public int $lastPage;

    public function __construct(public array $items, public int $total, public int $perPage, public int $page)
    {
        $this->lastPage = max(1, (int) ceil($total / max(1, $perPage)));
    }

    public function getIterator(): \ArrayIterator { return new \ArrayIterator($this->items); }
    public function count(): int { return count($this->items); }
    public function from(): int { return $this->total ? ($this->page - 1) * $this->perPage + 1 : 0; }
    public function to(): int { return min($this->total, $this->page * $this->perPage); }

    private function pageUrl(int $page): string
    {
        $query = $_GET;
        $query['page'] = $page;
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return e($path . '?' . http_build_query($query));
    }

    public function links(): string
    {
        if ($this->lastPage <= 1) {
            return '<div class="pagination-info">Showing ' . $this->from() . '–' . $this->to() . ' of ' . $this->total . '</div>';
        }
        $html = '<nav class="pagination"><span class="pagination-info">Showing ' . $this->from() . '–' . $this->to() . ' of ' . $this->total . '</span><div class="pagination-links">';
        $html .= $this->page > 1 ? '<a href="' . $this->pageUrl($this->page - 1) . '">&lsaquo;</a>' : '<span class="disabled">&lsaquo;</span>';
        $start = max(1, $this->page - 2);
        $end = min($this->lastPage, $this->page + 2);
        for ($i = $start; $i <= $end; $i++) {
            $html .= $i === $this->page ? '<span class="active">' . $i . '</span>' : '<a href="' . $this->pageUrl($i) . '">' . $i . '</a>';
        }
        $html .= $this->page < $this->lastPage ? '<a href="' . $this->pageUrl($this->page + 1) . '">&rsaquo;</a>' : '<span class="disabled">&rsaquo;</span>';
        return $html . '</div></nav>';
    }
}
