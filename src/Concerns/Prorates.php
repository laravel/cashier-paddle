<?php

namespace Laravel\Paddle\Concerns;

trait Prorates
{
    /**
     * Indicates if the plan change should be prorated.
     *
     * @var bool
     */
    protected $prorate = true;

    /**
     * Indicate that the plan change should not be prorated.
     *
     * @return $this
     */
    public function noProrate()
    {
        $this->prorate = false;

        return $this;
    }

    /**
     * Indicate that the plan change should be prorated.
     *
     * @return $this
     */
    public function prorate()
    {
        $this->prorate = true;

        return $this;
    }

    /**
     * Set the prorating behavior for the plan change.
     *
     * @param  bool  $prorate
     * @return $this
     */
    public function setProration($prorate = true)
    {
        $this->prorate = $prorate;

        return $this;
    }
}
