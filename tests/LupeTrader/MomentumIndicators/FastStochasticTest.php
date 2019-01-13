<?php

namespace LupeCode\phpTraderNativeTest\LupeTrader\MomentumIndicators;

use LupeCode\phpTraderNative\LupeTrader\Core\Exception;
use LupeCode\phpTraderNative\LupeTrader\MomentumIndicators\FastStochastic;
use LupeCode\phpTraderNative\LupeTrader\OverlapStudies\MovingAverage;
use LupeCode\phpTraderNativeTest\TestingTrait;
use PHPUnit\Framework\TestCase;

class FastStochasticTest extends TestCase
{
    use TestingTrait;

    /**
     * @throws Exception
     */
    public function testCalculate()
    {
        $optInFastK_Period = 14;
        $optInFastD_Period = 3;
        $optInFastD_MAType = MovingAverage::SIMPLE_MOVING_AVERAGE;
        list($traderFastK, $traderFastD) = \trader_stochf($this->High, $this->Low, $this->Close, $optInFastK_Period, $optInFastD_Period, $optInFastD_MAType);
        $fast = new FastStochastic();
        $fast
            ->setInputHigh($this->High)
            ->setInputLow($this->Low)
            ->setInputClose($this->Close)
            ->setInputFastKPeriod($optInFastK_Period)
            ->setInputFastDPeriod($optInFastD_Period)
            ->setInputMovingAverageType($optInFastD_MAType)
            ->calculate()
        ;
        $Output = [
            'FastK' => array_slice($fast->getOutputFastK(), $optInFastD_Period - 1, null, true),
            // The LupeTrader returns FastK earlier than FastD. ta-lib does not. To compare them we need to slice the extra off.
            'FastD' => $fast->getOutputFastD(),
        ];
        $this->assertEquals($traderFastK, $Output['FastK'], '', 0.01);
        $this->assertEquals($traderFastD, $Output['FastD'], '', 0.01);

    }
}
