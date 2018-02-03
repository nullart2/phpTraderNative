<?php

namespace LupeCode\phpTraderNative\TALib\Core;

use LupeCode\phpTraderNative\TALib\Classes\CandleSetting;
use LupeCode\phpTraderNative\TALib\Classes\MyInteger;
use LupeCode\phpTraderNative\TALib\Enum\CandleSettingType;
use LupeCode\phpTraderNative\TALib\Enum\Compatibility;
use LupeCode\phpTraderNative\TALib\Enum\MovingAverageType;
use LupeCode\phpTraderNative\TALib\Enum\RangeType;
use LupeCode\phpTraderNative\TALib\Enum\ReturnCode;
use LupeCode\phpTraderNative\TALib\Enum\UnstablePeriodFunctionID;

class OverlapStudies extends Core
{
    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param float[]   $inReal
     * @param int       $optInTimePeriod
     * @param float     $optInNbDevUp
     * @param float     $optInNbDevDn
     * @param int       $optInMAType
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param float[]   $outRealUpperBand
     * @param float[]   $outRealMiddleBand
     * @param float[]   $outRealLowerBand
     *
     * @return int
     */
    public function bbands(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, float $optInNbDevUp, float $optInNbDevDn, int $optInMAType, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outRealUpperBand, array &$outRealMiddleBand, array &$outRealLowerBand): int
    {
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 5;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        if ($optInNbDevUp == (-4e+37)) {
            $optInNbDevUp = 2.000000e+0;
        } elseif (($optInNbDevUp < -3.000000e+37) || ($optInNbDevUp > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($optInNbDevDn == (-4e+37)) {
            $optInNbDevDn = 2.000000e+0;
        } elseif (($optInNbDevDn < -3.000000e+37) || ($optInNbDevDn > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($inReal == $outRealUpperBand) {
            $tempBuffer1 = $outRealMiddleBand;
            $tempBuffer2 = $outRealLowerBand;
        } elseif ($inReal == $outRealLowerBand) {
            $tempBuffer1 = $outRealMiddleBand;
            $tempBuffer2 = $outRealUpperBand;
        } elseif ($inReal == $outRealMiddleBand) {
            $tempBuffer1 = $outRealLowerBand;
            $tempBuffer2 = $outRealUpperBand;
        } else {
            $tempBuffer1 = $outRealMiddleBand;
            $tempBuffer2 = $outRealUpperBand;
        }
        if (($tempBuffer1 == $inReal) || ($tempBuffer2 == $inReal)) {
            return ReturnCode::BadParam;
        }
        $ReturnCode = $this->movingAverage($startIdx, $endIdx, $inReal, $optInTimePeriod, $optInMAType, $outBegIdx, $outNBElement, $tempBuffer1);
        if (($ReturnCode != ReturnCode::Success) || ((int)$outNBElement->value == 0)) {
            $outNBElement->value = 0;

            return $ReturnCode;
        }
        if ($optInMAType == MovingAverageType::SMA) {
            $this->TA_INT_stddev_using_precalc_ma($inReal, $tempBuffer1, (int)$outBegIdx->value, (int)$outNBElement->value, $optInTimePeriod, $tempBuffer2);
        } else {
            $ReturnCode = (new StatisticFunctions())->stdDev((int)$outBegIdx->value, $endIdx, $inReal, $optInTimePeriod, 1.0, $outBegIdx, $outNBElement, $tempBuffer2);
            if ($ReturnCode != ReturnCode::Success) {
                $outNBElement->value = 0;

                return $ReturnCode;
            }
        }
        if ($tempBuffer1 != $outRealMiddleBand) {
            $outRealMiddleBand = \array_slice($tempBuffer1, 0, $outNBElement->value);
        }
        if ($optInNbDevUp == $optInNbDevDn) {
            if ($optInNbDevUp == 1.0) {
                for ($i = 0; $i < (int)$outNBElement->value; $i++) {
                    $tempReal             = $tempBuffer2[$i];
                    $tempReal2            = $outRealMiddleBand[$i];
                    $outRealUpperBand[$i] = $tempReal2 + $tempReal;
                    $outRealLowerBand[$i] = $tempReal2 - $tempReal;
                }
            } else {
                for ($i = 0; $i < (int)$outNBElement->value; $i++) {
                    $tempReal             = $tempBuffer2[$i] * $optInNbDevUp;
                    $tempReal2            = $outRealMiddleBand[$i];
                    $outRealUpperBand[$i] = $tempReal2 + $tempReal;
                    $outRealLowerBand[$i] = $tempReal2 - $tempReal;
                }
            }
        } elseif ($optInNbDevUp == 1.0) {
            for ($i = 0; $i < (int)$outNBElement->value; $i++) {
                $tempReal             = $tempBuffer2[$i];
                $tempReal2            = $outRealMiddleBand[$i];
                $outRealUpperBand[$i] = $tempReal2 + $tempReal;
                $outRealLowerBand[$i] = $tempReal2 - ($tempReal * $optInNbDevDn);
            }
        } elseif ($optInNbDevDn == 1.0) {
            for ($i = 0; $i < (int)$outNBElement->value; $i++) {
                $tempReal             = $tempBuffer2[$i];
                $tempReal2            = $outRealMiddleBand[$i];
                $outRealLowerBand[$i] = $tempReal2 - $tempReal;
                $outRealUpperBand[$i] = $tempReal2 + ($tempReal * $optInNbDevUp);
            }
        } else {
            for ($i = 0; $i < (int)$outNBElement->value; $i++) {
                $tempReal             = $tempBuffer2[$i];
                $tempReal2            = $outRealMiddleBand[$i];
                $outRealUpperBand[$i] = $tempReal2 + ($tempReal * $optInNbDevUp);
                $outRealLowerBand[$i] = $tempReal2 - ($tempReal * $optInNbDevDn);
            }
        }

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function dema(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //double[] $firstEMA;
        //double[] $secondEMA;
        //double $k;
        $firstEMABegIdx     = new MyInteger();
        $firstEMANbElement  = new MyInteger();
        $secondEMABegIdx    = new MyInteger();
        $secondEMANbElement = new MyInteger();
        //int $tempInt, $outIdx, $firstEMAIdx, $lookbackTotal, $lookbackEMA;
        //ReturnCode $ReturnCode;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 30;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        $outNBElement->value = 0;
        $outBegIdx->value    = 0;
        $lookbackEMA         = (new Lookback())->emaLookback($optInTimePeriod);
        $lookbackTotal       = $lookbackEMA * 2;
        if ($startIdx < $lookbackTotal) {
            $startIdx = $lookbackTotal;
        }
        if ($startIdx > $endIdx) {
            return ReturnCode::Success;
        }
        if ($inReal == $outReal) {
            $firstEMA = $outReal;
        } else {
            $tempInt  = $lookbackTotal + ($endIdx - $startIdx) + 1;
            $firstEMA = $this->double($tempInt);
        }
        $k          = ((double)2.0 / ((double)($optInTimePeriod + 1)));
        $ReturnCode = $this->TA_INT_EMA(
            $startIdx - $lookbackEMA, $endIdx, $inReal,
            $optInTimePeriod, $k,
            $firstEMABegIdx, $firstEMANbElement,
            $firstEMA
        );
        if (($ReturnCode != ReturnCode::Success) || ($firstEMANbElement->value == 0)) {
            return $ReturnCode;
        }
        $secondEMA  = $this->double($firstEMANbElement->value);
        $ReturnCode = $this->TA_INT_EMA(
            0, $firstEMANbElement->value - 1, $firstEMA,
            $optInTimePeriod, $k,
            $secondEMABegIdx, $secondEMANbElement,
            $secondEMA
        );
        if (($ReturnCode != ReturnCode::Success) || ($secondEMANbElement->value == 0)) {
            return $ReturnCode;
        }
        $firstEMAIdx = $secondEMABegIdx->value;
        $outIdx      = 0;
        while ($outIdx < $secondEMANbElement->value) {
            $outReal[$outIdx] = (2.0 * $firstEMA[$firstEMAIdx++]) - $secondEMA[$outIdx];
            $outIdx++;
        }
        $outBegIdx->value    = $firstEMABegIdx->value + $secondEMABegIdx->value;
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function ema(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 30;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }

        return $this->TA_INT_EMA(
            $startIdx, $endIdx, $inReal,
            $optInTimePeriod,
            ((double)2.0 / ((double)($optInTimePeriod + 1))),
            $outBegIdx, $outNBElement, $outReal
        );
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function htTrendline(int $startIdx, int $endIdx, array $inReal, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //int $outIdx, $i;
        //int $lookbackTotal, $today;
        //double $tempReal, $tempReal2;
        //double $adjustedPrevPeriod, $period;
        //int $trailingWMAIdx;
        //double $periodWMASum, $periodWMASub, $trailingWMAValue;
        //double $smoothedValue;
        //double $iTrend1, $iTrend2, $iTrend3;
        $a = 0.0962;
        $b = 0.5769;
        //double $hilbertTempReal;
        //int $hilbertIdx;
        $detrender_Odd  = $this->double(3);
        $detrender_Even = $this->double(3);
        //double $detrender;
        //double $prev_detrender_Odd;
        //double $prev_detrender_Even;
        //double $prev_detrender_input_Odd;
        //double $prev_detrender_input_Even;
        $Q1_Odd  = $this->double(3);
        $Q1_Even = $this->double(3);
        //double $Q1;
        //double $prev_Q1_Odd;
        //double $prev_Q1_Even;
        //double $prev_Q1_input_Odd;
        //double $prev_Q1_input_Even;
        $jI_Odd  = $this->double(3);
        $jI_Even = $this->double(3);
        //double $jI;
        //double $prev_jI_Odd;
        //double $prev_jI_Even;
        //double $prev_jI_input_Odd;
        //double $prev_jI_input_Even;
        $jQ_Odd  = $this->double(3);
        $jQ_Even = $this->double(3);
        //double $jQ;
        //double $prev_jQ_Odd;
        //double $prev_jQ_Even;
        //double $prev_jQ_input_Odd;
        //double $prev_jQ_input_Even;
        //double $Q2, $I2, $prevQ2, $prevI2, $Re, $Im;
        //double $I1ForOddPrev2, $I1ForOddPrev3;
        //double $I1ForEvenPrev2, $I1ForEvenPrev3;
        //double $rad2Deg;
        //double $todayValue, $smoothPeriod;
        $smoothPrice_Idx = 0;
        //double[] $smoothPrice;
        $maxIdx_smoothPricePrice = (50 - 1);
        //int $idxothPricePrice;
        //int $DCPeriodInt;
        //double $DCPeriod;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        {
            $smoothPrice = $this->double($maxIdx_smoothPricePrice + 1);
        };
        $iTrend1       = $iTrend2 = $iTrend3 = 0.0;
        $tempReal      = atan(1);
        $rad2Deg       = 45.0 / $tempReal;
        $lookbackTotal = 63 + ($this->unstablePeriod[UnstablePeriodFunctionID::HtTrendline]);
        if ($startIdx < $lookbackTotal) {
            $startIdx = $lookbackTotal;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        $outBegIdx->value = $startIdx;
        $trailingWMAIdx   = $startIdx - $lookbackTotal;
        $today            = $trailingWMAIdx;
        $tempReal         = $inReal[$today++];
        $periodWMASub     = $tempReal;
        $periodWMASum     = $tempReal;
        $tempReal         = $inReal[$today++];
        $periodWMASub     += $tempReal;
        $periodWMASum     += $tempReal * 2.0;
        $tempReal         = $inReal[$today++];
        $periodWMASub     += $tempReal;
        $periodWMASum     += $tempReal * 3.0;
        $trailingWMAValue = 0.0;
        $i                = 34;
        do {
            $tempReal = $inReal[$today++];
            {
                $periodWMASub     += $tempReal;
                $periodWMASub     -= $trailingWMAValue;
                $periodWMASum     += $tempReal * 4.0;
                $trailingWMAValue = $inReal[$trailingWMAIdx++];
                $smoothedValue    = $periodWMASum * 0.1;
                $periodWMASum     -= $periodWMASub;
            };
        } while (--$i != 0);
        $hilbertIdx = 0;
        {
            $detrender_Odd[0]          = 0.0;
            $detrender_Odd[1]          = 0.0;
            $detrender_Odd[2]          = 0.0;
            $detrender_Even[0]         = 0.0;
            $detrender_Even[1]         = 0.0;
            $detrender_Even[2]         = 0.0;
            $detrender                 = 0.0;
            $prev_detrender_Odd        = 0.0;
            $prev_detrender_Even       = 0.0;
            $prev_detrender_input_Odd  = 0.0;
            $prev_detrender_input_Even = 0.0;
        };
        {
            $Q1_Odd[0]          = 0.0;
            $Q1_Odd[1]          = 0.0;
            $Q1_Odd[2]          = 0.0;
            $Q1_Even[0]         = 0.0;
            $Q1_Even[1]         = 0.0;
            $Q1_Even[2]         = 0.0;
            $Q1                 = 0.0;
            $prev_Q1_Odd        = 0.0;
            $prev_Q1_Even       = 0.0;
            $prev_Q1_input_Odd  = 0.0;
            $prev_Q1_input_Even = 0.0;
        };
        {
            $jI_Odd[0]          = 0.0;
            $jI_Odd[1]          = 0.0;
            $jI_Odd[2]          = 0.0;
            $jI_Even[0]         = 0.0;
            $jI_Even[1]         = 0.0;
            $jI_Even[2]         = 0.0;
            $jI                 = 0.0;
            $prev_jI_Odd        = 0.0;
            $prev_jI_Even       = 0.0;
            $prev_jI_input_Odd  = 0.0;
            $prev_jI_input_Even = 0.0;
        };
        {
            $jQ_Odd[0]          = 0.0;
            $jQ_Odd[1]          = 0.0;
            $jQ_Odd[2]          = 0.0;
            $jQ_Even[0]         = 0.0;
            $jQ_Even[1]         = 0.0;
            $jQ_Even[2]         = 0.0;
            $jQ                 = 0.0;
            $prev_jQ_Odd        = 0.0;
            $prev_jQ_Even       = 0.0;
            $prev_jQ_input_Odd  = 0.0;
            $prev_jQ_input_Even = 0.0;
        };
        $period        = 0.0;
        $outIdx        = 0;
        $prevI2        = $prevQ2 = 0.0;
        $Re            = $Im = 0.0;
        $I1ForOddPrev3 = $I1ForEvenPrev3 = 0.0;
        $I1ForOddPrev2 = $I1ForEvenPrev2 = 0.0;
        $smoothPeriod  = 0.0;
        for ($i = 0; $i < 50; $i++) {
            $smoothPrice[$i] = 0.0;
        }
        while ($today <= $endIdx) {
            $adjustedPrevPeriod = (0.075 * $period) + 0.54;
            $todayValue         = $inReal[$today];
            {
                $periodWMASub     += $todayValue;
                $periodWMASub     -= $trailingWMAValue;
                $periodWMASum     += $todayValue * 4.0;
                $trailingWMAValue = $inReal[$trailingWMAIdx++];
                $smoothedValue    = $periodWMASum * 0.1;
                $periodWMASum     -= $periodWMASub;
            };
            $smoothPrice[$smoothPrice_Idx] = $smoothedValue;
            if (($today % 2) == 0) {
                {
                    $hilbertTempReal             = $a * $smoothedValue;
                    $detrender                   = -$detrender_Even[$hilbertIdx];
                    $detrender_Even[$hilbertIdx] = $hilbertTempReal;
                    $detrender                   += $hilbertTempReal;
                    $detrender                   -= $prev_detrender_Even;
                    $prev_detrender_Even         = $b * $prev_detrender_input_Even;
                    $detrender                   += $prev_detrender_Even;
                    $prev_detrender_input_Even   = $smoothedValue;
                    $detrender                   *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal      = $a * $detrender;
                    $Q1                   = -$Q1_Even[$hilbertIdx];
                    $Q1_Even[$hilbertIdx] = $hilbertTempReal;
                    $Q1                   += $hilbertTempReal;
                    $Q1                   -= $prev_Q1_Even;
                    $prev_Q1_Even         = $b * $prev_Q1_input_Even;
                    $Q1                   += $prev_Q1_Even;
                    $prev_Q1_input_Even   = $detrender;
                    $Q1                   *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal      = $a * $I1ForEvenPrev3;
                    $jI                   = -$jI_Even[$hilbertIdx];
                    $jI_Even[$hilbertIdx] = $hilbertTempReal;
                    $jI                   += $hilbertTempReal;
                    $jI                   -= $prev_jI_Even;
                    $prev_jI_Even         = $b * $prev_jI_input_Even;
                    $jI                   += $prev_jI_Even;
                    $prev_jI_input_Even   = $I1ForEvenPrev3;
                    $jI                   *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal      = $a * $Q1;
                    $jQ                   = -$jQ_Even[$hilbertIdx];
                    $jQ_Even[$hilbertIdx] = $hilbertTempReal;
                    $jQ                   += $hilbertTempReal;
                    $jQ                   -= $prev_jQ_Even;
                    $prev_jQ_Even         = $b * $prev_jQ_input_Even;
                    $jQ                   += $prev_jQ_Even;
                    $prev_jQ_input_Even   = $Q1;
                    $jQ                   *= $adjustedPrevPeriod;
                };
                if (++$hilbertIdx == 3) {
                    $hilbertIdx = 0;
                }
                $Q2            = (0.2 * ($Q1 + $jI)) + (0.8 * $prevQ2);
                $I2            = (0.2 * ($I1ForEvenPrev3 - $jQ)) + (0.8 * $prevI2);
                $I1ForOddPrev3 = $I1ForOddPrev2;
                $I1ForOddPrev2 = $detrender;
            } else {
                {
                    $hilbertTempReal            = $a * $smoothedValue;
                    $detrender                  = -$detrender_Odd[$hilbertIdx];
                    $detrender_Odd[$hilbertIdx] = $hilbertTempReal;
                    $detrender                  += $hilbertTempReal;
                    $detrender                  -= $prev_detrender_Odd;
                    $prev_detrender_Odd         = $b * $prev_detrender_input_Odd;
                    $detrender                  += $prev_detrender_Odd;
                    $prev_detrender_input_Odd   = $smoothedValue;
                    $detrender                  *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal     = $a * $detrender;
                    $Q1                  = -$Q1_Odd[$hilbertIdx];
                    $Q1_Odd[$hilbertIdx] = $hilbertTempReal;
                    $Q1                  += $hilbertTempReal;
                    $Q1                  -= $prev_Q1_Odd;
                    $prev_Q1_Odd         = $b * $prev_Q1_input_Odd;
                    $Q1                  += $prev_Q1_Odd;
                    $prev_Q1_input_Odd   = $detrender;
                    $Q1                  *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal     = $a * $I1ForOddPrev3;
                    $jI                  = -$jI_Odd[$hilbertIdx];
                    $jI_Odd[$hilbertIdx] = $hilbertTempReal;
                    $jI                  += $hilbertTempReal;
                    $jI                  -= $prev_jI_Odd;
                    $prev_jI_Odd         = $b * $prev_jI_input_Odd;
                    $jI                  += $prev_jI_Odd;
                    $prev_jI_input_Odd   = $I1ForOddPrev3;
                    $jI                  *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal     = $a * $Q1;
                    $jQ                  = -$jQ_Odd[$hilbertIdx];
                    $jQ_Odd[$hilbertIdx] = $hilbertTempReal;
                    $jQ                  += $hilbertTempReal;
                    $jQ                  -= $prev_jQ_Odd;
                    $prev_jQ_Odd         = $b * $prev_jQ_input_Odd;
                    $jQ                  += $prev_jQ_Odd;
                    $prev_jQ_input_Odd   = $Q1;
                    $jQ                  *= $adjustedPrevPeriod;
                };
                $Q2             = (0.2 * ($Q1 + $jI)) + (0.8 * $prevQ2);
                $I2             = (0.2 * ($I1ForOddPrev3 - $jQ)) + (0.8 * $prevI2);
                $I1ForEvenPrev3 = $I1ForEvenPrev2;
                $I1ForEvenPrev2 = $detrender;
            }
            $Re       = (0.2 * (($I2 * $prevI2) + ($Q2 * $prevQ2))) + (0.8 * $Re);
            $Im       = (0.2 * (($I2 * $prevQ2) - ($Q2 * $prevI2))) + (0.8 * $Im);
            $prevQ2   = $Q2;
            $prevI2   = $I2;
            $tempReal = $period;
            if (($Im != 0.0) && ($Re != 0.0)) {
                $period = 360.0 / (atan($Im / $Re) * $rad2Deg);
            }
            $tempReal2 = 1.5 * $tempReal;
            if ($period > $tempReal2) {
                $period = $tempReal2;
            }
            $tempReal2 = 0.67 * $tempReal;
            if ($period < $tempReal2) {
                $period = $tempReal2;
            }
            if ($period < 6) {
                $period = 6;
            } elseif ($period > 50) {
                $period = 50;
            }
            $period           = (0.2 * $period) + (0.8 * $tempReal);
            $smoothPeriod     = (0.33 * $period) + (0.67 * $smoothPeriod);
            $DCPeriod         = $smoothPeriod + 0.5;
            $DCPeriodInt      = (int)$DCPeriod;
            $idxothPricePrice = $today;
            $tempReal         = 0.0;
            for ($i = 0; $i < $DCPeriodInt; $i++) {
                $tempReal += $inReal[$idxothPricePrice--];
            }
            if ($DCPeriodInt > 0) {
                $tempReal = $tempReal / (double)$DCPeriodInt;
            }
            $tempReal2 = (4.0 * $tempReal + 3.0 * $iTrend1 + 2.0 * $iTrend2 + $iTrend3) / 10.0;
            $iTrend3   = $iTrend2;
            $iTrend2   = $iTrend1;
            $iTrend1   = $tempReal;
            if ($today >= $startIdx) {
                $outReal[$outIdx++] = $tempReal2;
            }
            {
                $smoothPrice_Idx++;
                if ($smoothPrice_Idx > $maxIdx_smoothPricePrice) {
                    $smoothPrice_Idx = 0;
                }
            };
            $today++;
        }
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function kama(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        $constMax  = 2.0 / (30.0 + 1.0);
        $constDiff = 2.0 / (2.0 + 1.0) - $constMax;
        //double $tempReal, $tempReal2;
        //double $sumROC1, $periodROC, $prevKAMA;
        //int $i, $today, $outIdx, $lookbackTotal;
        //int $trailingIdx;
        //double $trailingValue;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 30;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        $outBegIdx->value    = 0;
        $outNBElement->value = 0;
        $lookbackTotal       = $optInTimePeriod + ($this->unstablePeriod[UnstablePeriodFunctionID::KAMA]);
        if ($startIdx < $lookbackTotal) {
            $startIdx = $lookbackTotal;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        $sumROC1     = 0.0;
        $today       = $startIdx - $lookbackTotal;
        $trailingIdx = $today;
        $i           = $optInTimePeriod;
        while ($i-- > 0) {
            $tempReal = $inReal[$today++];
            $tempReal -= $inReal[$today];
            $sumROC1  += abs($tempReal);
        }
        $prevKAMA      = $inReal[$today - 1];
        $tempReal      = $inReal[$today];
        $tempReal2     = $inReal[$trailingIdx++];
        $periodROC     = $tempReal - $tempReal2;
        $trailingValue = $tempReal2;
        if (($sumROC1 <= $periodROC) || (((-0.00000001) < $sumROC1) && ($sumROC1 < 0.00000001))) {
            $tempReal = 1.0;
        } else {
            $tempReal = abs($periodROC / $sumROC1);
        }
        $tempReal = ($tempReal * $constDiff) + $constMax;
        $tempReal *= $tempReal;
        $prevKAMA = (($inReal[$today++] - $prevKAMA) * $tempReal) + $prevKAMA;
        while ($today <= $startIdx) {
            $tempReal      = $inReal[$today];
            $tempReal2     = $inReal[$trailingIdx++];
            $periodROC     = $tempReal - $tempReal2;
            $sumROC1       -= abs($trailingValue - $tempReal2);
            $sumROC1       += abs($tempReal - $inReal[$today - 1]);
            $trailingValue = $tempReal2;
            if (($sumROC1 <= $periodROC) || (((-0.00000001) < $sumROC1) && ($sumROC1 < 0.00000001))) {
                $tempReal = 1.0;
            } else {
                $tempReal = abs($periodROC / $sumROC1);
            }
            $tempReal = ($tempReal * $constDiff) + $constMax;
            $tempReal *= $tempReal;
            $prevKAMA = (($inReal[$today++] - $prevKAMA) * $tempReal) + $prevKAMA;
        }
        $outReal[0]       = $prevKAMA;
        $outIdx           = 1;
        $outBegIdx->value = $today - 1;
        while ($today <= $endIdx) {
            $tempReal      = $inReal[$today];
            $tempReal2     = $inReal[$trailingIdx++];
            $periodROC     = $tempReal - $tempReal2;
            $sumROC1       -= abs($trailingValue - $tempReal2);
            $sumROC1       += abs($tempReal - $inReal[$today - 1]);
            $trailingValue = $tempReal2;
            if (($sumROC1 <= $periodROC) || (((-0.00000001) < $sumROC1) && ($sumROC1 < 0.00000001))) {
                $tempReal = 1.0;
            } else {
                $tempReal = abs($periodROC / $sumROC1);
            }
            $tempReal           = ($tempReal * $constDiff) + $constMax;
            $tempReal           *= $tempReal;
            $prevKAMA           = (($inReal[$today++] - $prevKAMA) * $tempReal) + $prevKAMA;
            $outReal[$outIdx++] = $prevKAMA;
        }
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param int       $optInMAType
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function movingAverage(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, int $optInMAType, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //double[] $dummyBuffer;
        //ReturnCode $ReturnCode;
        //int $nbElement;
        //int $outIdx, $todayIdx;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 30;
        } elseif (((int)$optInTimePeriod < 1) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        if ($optInTimePeriod == 1) {
            $nbElement           = $endIdx - $startIdx + 1;
            $outNBElement->value = $nbElement;
            for ($todayIdx = $startIdx, $outIdx = 0; $outIdx < $nbElement; $outIdx++, $todayIdx++) {
                $outReal[$outIdx] = $inReal[$todayIdx];
            }
            $outBegIdx->value = $startIdx;

            return ReturnCode::Success;
        }
        switch ($optInMAType) {
            case MovingAverageType::SMA:
                $ReturnCode = $this->sma(
                    $startIdx, $endIdx, $inReal, $optInTimePeriod,
                    $outBegIdx, $outNBElement, $outReal
                );
                break;
            case MovingAverageType::EMA:
                $ReturnCode = $this->ema(
                    $startIdx, $endIdx, $inReal, $optInTimePeriod,
                    $outBegIdx, $outNBElement, $outReal
                );
                break;
            case MovingAverageType::WMA:
                $ReturnCode = $this->wma(
                    $startIdx, $endIdx, $inReal, $optInTimePeriod,
                    $outBegIdx, $outNBElement, $outReal
                );
                break;
            case MovingAverageType::DEMA:
                $ReturnCode = $this->dema(
                    $startIdx, $endIdx, $inReal, $optInTimePeriod,
                    $outBegIdx, $outNBElement, $outReal
                );
                break;
            case MovingAverageType::TEMA:
                $ReturnCode = $this->tema(
                    $startIdx, $endIdx, $inReal, $optInTimePeriod,
                    $outBegIdx, $outNBElement, $outReal
                );
                break;
            case MovingAverageType::TRIMA:
                $ReturnCode = $this->trima(
                    $startIdx, $endIdx, $inReal, $optInTimePeriod,
                    $outBegIdx, $outNBElement, $outReal
                );
                break;
            case MovingAverageType::KAMA:
                $ReturnCode = $this->kama(
                    $startIdx, $endIdx, $inReal, $optInTimePeriod,
                    $outBegIdx, $outNBElement, $outReal
                );
                break;
            case MovingAverageType::MAMA:
                $dummyBuffer = $this->double(($endIdx - $startIdx + 1));
                $ReturnCode  = $this->mama(
                    $startIdx, $endIdx, $inReal, 0.5, 0.05,
                    $outBegIdx, $outNBElement,
                    $outReal, $dummyBuffer
                );
                break;
            case MovingAverageType::T3:
                $ReturnCode = $this->t3(
                    $startIdx, $endIdx, $inReal,
                    $optInTimePeriod, 0.7,
                    $outBegIdx, $outNBElement, $outReal
                );
                break;
            default:
                $ReturnCode = ReturnCode::BadParam;
                break;
        }

        return $ReturnCode;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param float     $optInFastLimit
     * @param float     $optInSlowLimit
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outMAMA
     * @param array     $outFAMA
     *
     * @return int
     */
    public function mama(int $startIdx, int $endIdx, array $inReal, float $optInFastLimit, float $optInSlowLimit, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outMAMA, array &$outFAMA): int
    {
        //int $outIdx, $i;
        //int $lookbackTotal, $today;
        //double $tempReal, $tempReal2;
        //double $adjustedPrevPeriod, $period;
        //int $trailingWMAIdx;
        //double $periodWMASum, $periodWMASub, $trailingWMAValue;
        //double $smoothedValue;
        $a = 0.0962;
        $b = 0.5769;
        //double $hilbertTempReal;
        //int $hilbertIdx;
        $detrender_Odd  = $this->double(3);
        $detrender_Even = $this->double(3);
        //double $detrender;
        //double $prev_detrender_Odd;
        //double $prev_detrender_Even;
        //double $prev_detrender_input_Odd;
        //double $prev_detrender_input_Even;
        $Q1_Odd  = $this->double(3);
        $Q1_Even = $this->double(3);
        //double $Q1;
        //double $prev_Q1_Odd;
        //double $prev_Q1_Even;
        //double $prev_Q1_input_Odd;
        //double $prev_Q1_input_Even;
        $jI_Odd  = $this->double(3);
        $jI_Even = $this->double(3);
        //double $jI;
        //double $prev_jI_Odd;
        //double $prev_jI_Even;
        //double $prev_jI_input_Odd;
        //double $prev_jI_input_Even;
        $jQ_Odd  = $this->double(3);
        $jQ_Even = $this->double(3);
        //double $jQ;
        //double $prev_jQ_Odd;
        //double $prev_jQ_Even;
        //double $prev_jQ_input_Odd;
        //double $prev_jQ_input_Even;
        //double $Q2, $I2, $prevQ2, $prevI2, $Re, $Im;
        //double $I1ForOddPrev2, $I1ForOddPrev3;
        //double $I1ForEvenPrev2, $I1ForEvenPrev3;
        //double $rad2Deg;
        //double $mama, $fama, $todayValue, $prevPhase;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ($optInFastLimit == (-4e+37)) {
            $optInFastLimit = 5.000000e-1;
        } elseif (($optInFastLimit < 1.000000e-2) || ($optInFastLimit > 9.900000e-1)) {
            return ReturnCode::BadParam;
        }
        if ($optInSlowLimit == (-4e+37)) {
            $optInSlowLimit = 5.000000e-2;
        } elseif (($optInSlowLimit < 1.000000e-2) || ($optInSlowLimit > 9.900000e-1)) {
            return ReturnCode::BadParam;
        }
        $rad2Deg       = 180.0 / (4.0 * atan(1));
        $lookbackTotal = 32 + ($this->unstablePeriod[UnstablePeriodFunctionID::MAMA]);
        if ($startIdx < $lookbackTotal) {
            $startIdx = $lookbackTotal;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        $outBegIdx->value = $startIdx;
        $trailingWMAIdx   = $startIdx - $lookbackTotal;
        $today            = $trailingWMAIdx;
        $tempReal         = $inReal[$today++];
        $periodWMASub     = $tempReal;
        $periodWMASum     = $tempReal;
        $tempReal         = $inReal[$today++];
        $periodWMASub     += $tempReal;
        $periodWMASum     += $tempReal * 2.0;
        $tempReal         = $inReal[$today++];
        $periodWMASub     += $tempReal;
        $periodWMASum     += $tempReal * 3.0;
        $trailingWMAValue = 0.0;
        $i                = 9;
        do {
            $tempReal = $inReal[$today++];
            {
                $periodWMASub     += $tempReal;
                $periodWMASub     -= $trailingWMAValue;
                $periodWMASum     += $tempReal * 4.0;
                $trailingWMAValue = $inReal[$trailingWMAIdx++];
                $smoothedValue    = $periodWMASum * 0.1;
                $periodWMASum     -= $periodWMASub;
            };
        } while (--$i != 0);
        $hilbertIdx = 0;
        {
            $detrender_Odd[0]          = 0.0;
            $detrender_Odd[1]          = 0.0;
            $detrender_Odd[2]          = 0.0;
            $detrender_Even[0]         = 0.0;
            $detrender_Even[1]         = 0.0;
            $detrender_Even[2]         = 0.0;
            $detrender                 = 0.0;
            $prev_detrender_Odd        = 0.0;
            $prev_detrender_Even       = 0.0;
            $prev_detrender_input_Odd  = 0.0;
            $prev_detrender_input_Even = 0.0;
        };
        {
            $Q1_Odd[0]          = 0.0;
            $Q1_Odd[1]          = 0.0;
            $Q1_Odd[2]          = 0.0;
            $Q1_Even[0]         = 0.0;
            $Q1_Even[1]         = 0.0;
            $Q1_Even[2]         = 0.0;
            $Q1                 = 0.0;
            $prev_Q1_Odd        = 0.0;
            $prev_Q1_Even       = 0.0;
            $prev_Q1_input_Odd  = 0.0;
            $prev_Q1_input_Even = 0.0;
        };
        {
            $jI_Odd[0]          = 0.0;
            $jI_Odd[1]          = 0.0;
            $jI_Odd[2]          = 0.0;
            $jI_Even[0]         = 0.0;
            $jI_Even[1]         = 0.0;
            $jI_Even[2]         = 0.0;
            $jI                 = 0.0;
            $prev_jI_Odd        = 0.0;
            $prev_jI_Even       = 0.0;
            $prev_jI_input_Odd  = 0.0;
            $prev_jI_input_Even = 0.0;
        };
        {
            $jQ_Odd[0]          = 0.0;
            $jQ_Odd[1]          = 0.0;
            $jQ_Odd[2]          = 0.0;
            $jQ_Even[0]         = 0.0;
            $jQ_Even[1]         = 0.0;
            $jQ_Even[2]         = 0.0;
            $jQ                 = 0.0;
            $prev_jQ_Odd        = 0.0;
            $prev_jQ_Even       = 0.0;
            $prev_jQ_input_Odd  = 0.0;
            $prev_jQ_input_Even = 0.0;
        };
        $period        = 0.0;
        $outIdx        = 0;
        $prevI2        = $prevQ2 = 0.0;
        $Re            = $Im = 0.0;
        $mama          = $fama = 0.0;
        $I1ForOddPrev3 = $I1ForEvenPrev3 = 0.0;
        $I1ForOddPrev2 = $I1ForEvenPrev2 = 0.0;
        $prevPhase     = 0.0;
        while ($today <= $endIdx) {
            $adjustedPrevPeriod = (0.075 * $period) + 0.54;
            $todayValue         = $inReal[$today];
            {
                $periodWMASub     += $todayValue;
                $periodWMASub     -= $trailingWMAValue;
                $periodWMASum     += $todayValue * 4.0;
                $trailingWMAValue = $inReal[$trailingWMAIdx++];
                $smoothedValue    = $periodWMASum * 0.1;
                $periodWMASum     -= $periodWMASub;
            };
            if (($today % 2) == 0) {
                {
                    $hilbertTempReal             = $a * $smoothedValue;
                    $detrender                   = -$detrender_Even[$hilbertIdx];
                    $detrender_Even[$hilbertIdx] = $hilbertTempReal;
                    $detrender                   += $hilbertTempReal;
                    $detrender                   -= $prev_detrender_Even;
                    $prev_detrender_Even         = $b * $prev_detrender_input_Even;
                    $detrender                   += $prev_detrender_Even;
                    $prev_detrender_input_Even   = $smoothedValue;
                    $detrender                   *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal      = $a * $detrender;
                    $Q1                   = -$Q1_Even[$hilbertIdx];
                    $Q1_Even[$hilbertIdx] = $hilbertTempReal;
                    $Q1                   += $hilbertTempReal;
                    $Q1                   -= $prev_Q1_Even;
                    $prev_Q1_Even         = $b * $prev_Q1_input_Even;
                    $Q1                   += $prev_Q1_Even;
                    $prev_Q1_input_Even   = $detrender;
                    $Q1                   *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal      = $a * $I1ForEvenPrev3;
                    $jI                   = -$jI_Even[$hilbertIdx];
                    $jI_Even[$hilbertIdx] = $hilbertTempReal;
                    $jI                   += $hilbertTempReal;
                    $jI                   -= $prev_jI_Even;
                    $prev_jI_Even         = $b * $prev_jI_input_Even;
                    $jI                   += $prev_jI_Even;
                    $prev_jI_input_Even   = $I1ForEvenPrev3;
                    $jI                   *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal      = $a * $Q1;
                    $jQ                   = -$jQ_Even[$hilbertIdx];
                    $jQ_Even[$hilbertIdx] = $hilbertTempReal;
                    $jQ                   += $hilbertTempReal;
                    $jQ                   -= $prev_jQ_Even;
                    $prev_jQ_Even         = $b * $prev_jQ_input_Even;
                    $jQ                   += $prev_jQ_Even;
                    $prev_jQ_input_Even   = $Q1;
                    $jQ                   *= $adjustedPrevPeriod;
                };
                if (++$hilbertIdx == 3) {
                    $hilbertIdx = 0;
                }
                $Q2            = (0.2 * ($Q1 + $jI)) + (0.8 * $prevQ2);
                $I2            = (0.2 * ($I1ForEvenPrev3 - $jQ)) + (0.8 * $prevI2);
                $I1ForOddPrev3 = $I1ForOddPrev2;
                $I1ForOddPrev2 = $detrender;
                if ($I1ForEvenPrev3 != 0.0) {
                    $tempReal2 = (atan($Q1 / $I1ForEvenPrev3) * $rad2Deg);
                } else {
                    $tempReal2 = 0.0;
                }
            } else {
                {
                    $hilbertTempReal            = $a * $smoothedValue;
                    $detrender                  = -$detrender_Odd[$hilbertIdx];
                    $detrender_Odd[$hilbertIdx] = $hilbertTempReal;
                    $detrender                  += $hilbertTempReal;
                    $detrender                  -= $prev_detrender_Odd;
                    $prev_detrender_Odd         = $b * $prev_detrender_input_Odd;
                    $detrender                  += $prev_detrender_Odd;
                    $prev_detrender_input_Odd   = $smoothedValue;
                    $detrender                  *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal     = $a * $detrender;
                    $Q1                  = -$Q1_Odd[$hilbertIdx];
                    $Q1_Odd[$hilbertIdx] = $hilbertTempReal;
                    $Q1                  += $hilbertTempReal;
                    $Q1                  -= $prev_Q1_Odd;
                    $prev_Q1_Odd         = $b * $prev_Q1_input_Odd;
                    $Q1                  += $prev_Q1_Odd;
                    $prev_Q1_input_Odd   = $detrender;
                    $Q1                  *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal     = $a * $I1ForOddPrev3;
                    $jI                  = -$jI_Odd[$hilbertIdx];
                    $jI_Odd[$hilbertIdx] = $hilbertTempReal;
                    $jI                  += $hilbertTempReal;
                    $jI                  -= $prev_jI_Odd;
                    $prev_jI_Odd         = $b * $prev_jI_input_Odd;
                    $jI                  += $prev_jI_Odd;
                    $prev_jI_input_Odd   = $I1ForOddPrev3;
                    $jI                  *= $adjustedPrevPeriod;
                };
                {
                    $hilbertTempReal     = $a * $Q1;
                    $jQ                  = -$jQ_Odd[$hilbertIdx];
                    $jQ_Odd[$hilbertIdx] = $hilbertTempReal;
                    $jQ                  += $hilbertTempReal;
                    $jQ                  -= $prev_jQ_Odd;
                    $prev_jQ_Odd         = $b * $prev_jQ_input_Odd;
                    $jQ                  += $prev_jQ_Odd;
                    $prev_jQ_input_Odd   = $Q1;
                    $jQ                  *= $adjustedPrevPeriod;
                };
                $Q2             = (0.2 * ($Q1 + $jI)) + (0.8 * $prevQ2);
                $I2             = (0.2 * ($I1ForOddPrev3 - $jQ)) + (0.8 * $prevI2);
                $I1ForEvenPrev3 = $I1ForEvenPrev2;
                $I1ForEvenPrev2 = $detrender;
                if ($I1ForOddPrev3 != 0.0) {
                    $tempReal2 = (atan($Q1 / $I1ForOddPrev3) * $rad2Deg);
                } else {
                    $tempReal2 = 0.0;
                }
            }
            $tempReal  = $prevPhase - $tempReal2;
            $prevPhase = $tempReal2;
            if ($tempReal < 1.0) {
                $tempReal = 1.0;
            }
            if ($tempReal > 1.0) {
                $tempReal = $optInFastLimit / $tempReal;
                if ($tempReal < $optInSlowLimit) {
                    $tempReal = $optInSlowLimit;
                }
            } else {
                $tempReal = $optInFastLimit;
            }
            $mama     = ($tempReal * $todayValue) + ((1 - $tempReal) * $mama);
            $tempReal *= 0.5;
            $fama     = ($tempReal * $mama) + ((1 - $tempReal) * $fama);
            if ($today >= $startIdx) {
                $outMAMA[$outIdx]   = $mama;
                $outFAMA[$outIdx++] = $fama;
            }
            $Re       = (0.2 * (($I2 * $prevI2) + ($Q2 * $prevQ2))) + (0.8 * $Re);
            $Im       = (0.2 * (($I2 * $prevQ2) - ($Q2 * $prevI2))) + (0.8 * $Im);
            $prevQ2   = $Q2;
            $prevI2   = $I2;
            $tempReal = $period;
            if (($Im != 0.0) && ($Re != 0.0)) {
                $period = 360.0 / (atan($Im / $Re) * $rad2Deg);
            }
            $tempReal2 = 1.5 * $tempReal;
            if ($period > $tempReal2) {
                $period = $tempReal2;
            }
            $tempReal2 = 0.67 * $tempReal;
            if ($period < $tempReal2) {
                $period = $tempReal2;
            }
            if ($period < 6) {
                $period = 6;
            } elseif ($period > 50) {
                $period = 50;
            }
            $period = (0.2 * $period) + (0.8 * $tempReal);
            $today++;
        }
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param array     $inPeriods
     * @param int       $optInMinPeriod
     * @param int       $optInMaxPeriod
     * @param int       $optInMAType
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function movingAverageVariablePeriod(int $startIdx, int $endIdx, array $inReal, array &$inPeriods, int $optInMinPeriod, int $optInMaxPeriod, int $optInMAType, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //int $i, $j, $lookbackTotal, $outputSize, $tempInt, $curPeriod;
        //int[] $localPeriodArray;
        //double[] $localOutputArray;
        $localBegIdx    = new MyInteger();
        $localNbElement = new MyInteger();
        //ReturnCode $ReturnCode;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInMinPeriod == (PHP_INT_MIN)) {
            $optInMinPeriod = 2;
        } elseif (((int)$optInMinPeriod < 2) || ((int)$optInMinPeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        if ((int)$optInMaxPeriod == (PHP_INT_MIN)) {
            $optInMaxPeriod = 30;
        } elseif (((int)$optInMaxPeriod < 2) || ((int)$optInMaxPeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        $lookbackTotal = (new Lookback())->movingAverageLookback($optInMaxPeriod, $optInMAType);
        if ($startIdx < $lookbackTotal) {
            $startIdx = $lookbackTotal;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        if ($lookbackTotal > $startIdx) {
            $tempInt = $lookbackTotal;
        } else {
            $tempInt = $startIdx;
        }
        if ($tempInt > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        $outputSize       = $endIdx - $tempInt + 1;
        $localOutputArray = \array_pad([], $outputSize, 0.);
        $localPeriodArray = \array_pad([], $outputSize, 0);
        for ($i = 0; $i < $outputSize; $i++) {
            $tempInt = (int)($inPeriods[$startIdx + $i]);
            if ($tempInt < $optInMinPeriod) {
                $tempInt = $optInMinPeriod;
            } elseif ($tempInt > $optInMaxPeriod) {
                $tempInt = $optInMaxPeriod;
            }
            $localPeriodArray[$i] = $tempInt;
        }
        for ($i = 0; $i < $outputSize; $i++) {
            $curPeriod = $localPeriodArray[$i];
            if ($curPeriod != 0) {
                $ReturnCode = $this->movingAverage(
                    $startIdx, $endIdx, $inReal,
                    $curPeriod, $optInMAType,
                    $localBegIdx, $localNbElement, $localOutputArray
                );
                if ($ReturnCode != ReturnCode::Success) {
                    $outBegIdx->value    = 0;
                    $outNBElement->value = 0;

                    return $ReturnCode;
                }
                $outReal[$i] = $localOutputArray[$i];
                for ($j = $i + 1; $j < $outputSize; $j++) {
                    if ($localPeriodArray[$j] == $curPeriod) {
                        $localPeriodArray[$j] = 0;
                        $outReal[$j]          = $localOutputArray[$j];
                    }
                }
            }
        }
        $outBegIdx->value    = $startIdx;
        $outNBElement->value = $outputSize;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function midPoint(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //double $lowest, $highest, $tmp;
        //int $outIdx, $nbInitialElementNeeded;
        //int $trailingIdx, $today, $i;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 14;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        $nbInitialElementNeeded = ($optInTimePeriod - 1);
        if ($startIdx < $nbInitialElementNeeded) {
            $startIdx = $nbInitialElementNeeded;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        $outIdx      = 0;
        $today       = $startIdx;
        $trailingIdx = $startIdx - $nbInitialElementNeeded;
        while ($today <= $endIdx) {
            $lowest  = $inReal[$trailingIdx++];
            $highest = $lowest;
            for ($i = $trailingIdx; $i <= $today; $i++) {
                $tmp = $inReal[$i];
                if ($tmp < $lowest) {
                    $lowest = $tmp;
                } elseif ($tmp > $highest) {
                    $highest = $tmp;
                }
            }
            $outReal[$outIdx++] = ($highest + $lowest) / 2.0;
            $today++;
        }
        $outBegIdx->value    = $startIdx;
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inHigh
     * @param array     $inLow
     * @param int       $optInTimePeriod
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function midPrice(int $startIdx, int $endIdx, array $inHigh, array $inLow, int $optInTimePeriod, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //double $lowest, $highest, $tmp;
        //int $outIdx, $nbInitialElementNeeded;
        //int $trailingIdx, $today, $i;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 14;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        $nbInitialElementNeeded = ($optInTimePeriod - 1);
        if ($startIdx < $nbInitialElementNeeded) {
            $startIdx = $nbInitialElementNeeded;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        $outIdx      = 0;
        $today       = $startIdx;
        $trailingIdx = $startIdx - $nbInitialElementNeeded;
        while ($today <= $endIdx) {
            $lowest  = $inLow[$trailingIdx];
            $highest = $inHigh[$trailingIdx];
            $trailingIdx++;
            for ($i = $trailingIdx; $i <= $today; $i++) {
                $tmp = $inLow[$i];
                if ($tmp < $lowest) {
                    $lowest = $tmp;
                }
                $tmp = $inHigh[$i];
                if ($tmp > $highest) {
                    $highest = $tmp;
                }
            }
            $outReal[$outIdx++] = ($highest + $lowest) / 2.0;
            $today++;
        }
        $outBegIdx->value    = $startIdx;
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inHigh
     * @param array     $inLow
     * @param float     $optInAcceleration
     * @param float     $optInMaximum
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function sar(int $startIdx, int $endIdx, array $inHigh, array $inLow, float $optInAcceleration, float $optInMaximum, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //ReturnCode $ReturnCode;
        //int $isLong;
        //int $todayIdx, $outIdx;
        $tempInt = new MyInteger();
        //double $newHigh, $newLow, $prevHigh, $prevLow;
        //double $af, $ep, $sar;
        $ep_temp = $this->double(1);
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ($optInAcceleration == (-4e+37)) {
            $optInAcceleration = 2.000000e-2;
        } elseif (($optInAcceleration < 0.000000e+0) || ($optInAcceleration > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($optInMaximum == (-4e+37)) {
            $optInMaximum = 2.000000e-1;
        } elseif (($optInMaximum < 0.000000e+0) || ($optInMaximum > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($startIdx < 1) {
            $startIdx = 1;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        $af = $optInAcceleration;
        if ($af > $optInMaximum) {
            $af = $optInAcceleration = $optInMaximum;
        }
        $ReturnCode = (new MomentumIndicators())->minusDM(
            $startIdx, $startIdx, $inHigh, $inLow, 1,
            $tempInt, $tempInt,
            $ep_temp
        );
        if ($ep_temp[0] > 0) {
            $isLong = 0;
        } else {
            $isLong = 1;
        }
        if ($ReturnCode != ReturnCode::Success) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return $ReturnCode;
        }
        $outBegIdx->value = $startIdx;
        $outIdx           = 0;
        $todayIdx         = $startIdx;
        $newHigh          = $inHigh[$todayIdx - 1];
        $newLow           = $inLow[$todayIdx - 1];
        if ($isLong == 1) {
            $ep  = $inHigh[$todayIdx];
            $sar = $newLow;
        } else {
            $ep  = $inLow[$todayIdx];
            $sar = $newHigh;
        }
        $newLow  = $inLow[$todayIdx];
        $newHigh = $inHigh[$todayIdx];
        while ($todayIdx <= $endIdx) {
            $prevLow  = $newLow;
            $prevHigh = $newHigh;
            $newLow   = $inLow[$todayIdx];
            $newHigh  = $inHigh[$todayIdx];
            $todayIdx++;
            if ($isLong == 1) {
                if ($newLow <= $sar) {
                    $isLong = 0;
                    $sar    = $ep;
                    if ($sar < $prevHigh) {
                        $sar = $prevHigh;
                    }
                    if ($sar < $newHigh) {
                        $sar = $newHigh;
                    }
                    $outReal[$outIdx++] = $sar;
                    $af                 = $optInAcceleration;
                    $ep                 = $newLow;
                    $sar                = $sar + $af * ($ep - $sar);
                    if ($sar < $prevHigh) {
                        $sar = $prevHigh;
                    }
                    if ($sar < $newHigh) {
                        $sar = $newHigh;
                    }
                } else {
                    $outReal[$outIdx++] = $sar;
                    if ($newHigh > $ep) {
                        $ep = $newHigh;
                        $af += $optInAcceleration;
                        if ($af > $optInMaximum) {
                            $af = $optInMaximum;
                        }
                    }
                    $sar = $sar + $af * ($ep - $sar);
                    if ($sar > $prevLow) {
                        $sar = $prevLow;
                    }
                    if ($sar > $newLow) {
                        $sar = $newLow;
                    }
                }
            } else {
                if ($newHigh >= $sar) {
                    $isLong = 1;
                    $sar    = $ep;
                    if ($sar > $prevLow) {
                        $sar = $prevLow;
                    }
                    if ($sar > $newLow) {
                        $sar = $newLow;
                    }
                    $outReal[$outIdx++] = $sar;
                    $af                 = $optInAcceleration;
                    $ep                 = $newHigh;
                    $sar                = $sar + $af * ($ep - $sar);
                    if ($sar > $prevLow) {
                        $sar = $prevLow;
                    }
                    if ($sar > $newLow) {
                        $sar = $newLow;
                    }
                } else {
                    $outReal[$outIdx++] = $sar;
                    if ($newLow < $ep) {
                        $ep = $newLow;
                        $af += $optInAcceleration;
                        if ($af > $optInMaximum) {
                            $af = $optInMaximum;
                        }
                    }
                    $sar = $sar + $af * ($ep - $sar);
                    if ($sar < $prevHigh) {
                        $sar = $prevHigh;
                    }
                    if ($sar < $newHigh) {
                        $sar = $newHigh;
                    }
                }
            }
        }
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inHigh
     * @param array     $inLow
     * @param float     $optInStartValue
     * @param float     $optInOffsetOnReverse
     * @param float     $optInAccelerationInitLong
     * @param float     $optInAccelerationLong
     * @param float     $optInAccelerationMaxLong
     * @param float     $optInAccelerationInitShort
     * @param float     $optInAccelerationShort
     * @param float     $optInAccelerationMaxShort
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function sarExt(int $startIdx, int $endIdx, array $inHigh, array $inLow, float $optInStartValue, float $optInOffsetOnReverse, float $optInAccelerationInitLong, float $optInAccelerationLong, float $optInAccelerationMaxLong, float $optInAccelerationInitShort, float $optInAccelerationShort, float $optInAccelerationMaxShort, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //ReturnCode $ReturnCode;
        //int $isLong;
        //int $todayIdx, $outIdx;
        $tempInt = new MyInteger();
        //double $newHigh, $newLow, $prevHigh, $prevLow;
        //double $afLong, $afShort, $ep, $sar;
        $ep_temp = $this->double(1);
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ($optInStartValue == (-4e+37)) {
            $optInStartValue = 0.000000e+0;
        } elseif (($optInStartValue < -3.000000e+37) || ($optInStartValue > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($optInOffsetOnReverse == (-4e+37)) {
            $optInOffsetOnReverse = 0.000000e+0;
        } elseif (($optInOffsetOnReverse < 0.000000e+0) || ($optInOffsetOnReverse > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($optInAccelerationInitLong == (-4e+37)) {
            $optInAccelerationInitLong = 2.000000e-2;
        } elseif (($optInAccelerationInitLong < 0.000000e+0) || ($optInAccelerationInitLong > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($optInAccelerationLong == (-4e+37)) {
            $optInAccelerationLong = 2.000000e-2;
        } elseif (($optInAccelerationLong < 0.000000e+0) || ($optInAccelerationLong > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($optInAccelerationMaxLong == (-4e+37)) {
            $optInAccelerationMaxLong = 2.000000e-1;
        } elseif (($optInAccelerationMaxLong < 0.000000e+0) || ($optInAccelerationMaxLong > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($optInAccelerationInitShort == (-4e+37)) {
            $optInAccelerationInitShort = 2.000000e-2;
        } elseif (($optInAccelerationInitShort < 0.000000e+0) || ($optInAccelerationInitShort > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($optInAccelerationShort == (-4e+37)) {
            $optInAccelerationShort = 2.000000e-2;
        } elseif (($optInAccelerationShort < 0.000000e+0) || ($optInAccelerationShort > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($optInAccelerationMaxShort == (-4e+37)) {
            $optInAccelerationMaxShort = 2.000000e-1;
        } elseif (($optInAccelerationMaxShort < 0.000000e+0) || ($optInAccelerationMaxShort > 3.000000e+37)) {
            return ReturnCode::BadParam;
        }
        if ($startIdx < 1) {
            $startIdx = 1;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        $afLong  = $optInAccelerationInitLong;
        $afShort = $optInAccelerationInitShort;
        if ($afLong > $optInAccelerationMaxLong) {
            $afLong = $optInAccelerationInitLong = $optInAccelerationMaxLong;
        }
        if ($optInAccelerationLong > $optInAccelerationMaxLong) {
            $optInAccelerationLong = $optInAccelerationMaxLong;
        }
        if ($afShort > $optInAccelerationMaxShort) {
            $afShort = $optInAccelerationInitShort = $optInAccelerationMaxShort;
        }
        if ($optInAccelerationShort > $optInAccelerationMaxShort) {
            $optInAccelerationShort = $optInAccelerationMaxShort;
        }
        if ($optInStartValue == 0) {
            $ReturnCode = (new MomentumIndicators())->minusDM(
                $startIdx, $startIdx, $inHigh, $inLow, 1,
                $tempInt, $tempInt,
                $ep_temp
            );
            if ($ep_temp[0] > 0) {
                $isLong = 0;
            } else {
                $isLong = 1;
            }
            if ($ReturnCode != ReturnCode::Success) {
                $outBegIdx->value    = 0;
                $outNBElement->value = 0;

                return $ReturnCode;
            }
        } elseif ($optInStartValue > 0) {
            $isLong = 1;
        } else {
            $isLong = 0;
        }
        $outBegIdx->value = $startIdx;
        $outIdx           = 0;
        $todayIdx         = $startIdx;
        $newHigh          = $inHigh[$todayIdx - 1];
        $newLow           = $inLow[$todayIdx - 1];
        if ($optInStartValue == 0) {
            if ($isLong == 1) {
                $ep  = $inHigh[$todayIdx];
                $sar = $newLow;
            } else {
                $ep  = $inLow[$todayIdx];
                $sar = $newHigh;
            }
        } elseif ($optInStartValue > 0) {
            $ep  = $inHigh[$todayIdx];
            $sar = $optInStartValue;
        } else {
            $ep  = $inLow[$todayIdx];
            $sar = abs($optInStartValue);
        }
        $newLow  = $inLow[$todayIdx];
        $newHigh = $inHigh[$todayIdx];
        while ($todayIdx <= $endIdx) {
            $prevLow  = $newLow;
            $prevHigh = $newHigh;
            $newLow   = $inLow[$todayIdx];
            $newHigh  = $inHigh[$todayIdx];
            $todayIdx++;
            if ($isLong == 1) {
                if ($newLow <= $sar) {
                    $isLong = 0;
                    $sar    = $ep;
                    if ($sar < $prevHigh) {
                        $sar = $prevHigh;
                    }
                    if ($sar < $newHigh) {
                        $sar = $newHigh;
                    }
                    if ($optInOffsetOnReverse != 0.0) {
                        $sar += $sar * $optInOffsetOnReverse;
                    }
                    $outReal[$outIdx++] = -$sar;
                    $afShort            = $optInAccelerationInitShort;
                    $ep                 = $newLow;
                    $sar                = $sar + $afShort * ($ep - $sar);
                    if ($sar < $prevHigh) {
                        $sar = $prevHigh;
                    }
                    if ($sar < $newHigh) {
                        $sar = $newHigh;
                    }
                } else {
                    $outReal[$outIdx++] = $sar;
                    if ($newHigh > $ep) {
                        $ep     = $newHigh;
                        $afLong += $optInAccelerationLong;
                        if ($afLong > $optInAccelerationMaxLong) {
                            $afLong = $optInAccelerationMaxLong;
                        }
                    }
                    $sar = $sar + $afLong * ($ep - $sar);
                    if ($sar > $prevLow) {
                        $sar = $prevLow;
                    }
                    if ($sar > $newLow) {
                        $sar = $newLow;
                    }
                }
            } else {
                if ($newHigh >= $sar) {
                    $isLong = 1;
                    $sar    = $ep;
                    if ($sar > $prevLow) {
                        $sar = $prevLow;
                    }
                    if ($sar > $newLow) {
                        $sar = $newLow;
                    }
                    if ($optInOffsetOnReverse != 0.0) {
                        $sar -= $sar * $optInOffsetOnReverse;
                    }
                    $outReal[$outIdx++] = $sar;
                    $afLong             = $optInAccelerationInitLong;
                    $ep                 = $newHigh;
                    $sar                = $sar + $afLong * ($ep - $sar);
                    if ($sar > $prevLow) {
                        $sar = $prevLow;
                    }
                    if ($sar > $newLow) {
                        $sar = $newLow;
                    }
                } else {
                    $outReal[$outIdx++] = -$sar;
                    if ($newLow < $ep) {
                        $ep      = $newLow;
                        $afShort += $optInAccelerationShort;
                        if ($afShort > $optInAccelerationMaxShort) {
                            $afShort = $optInAccelerationMaxShort;
                        }
                    }
                    $sar = $sar + $afShort * ($ep - $sar);
                    if ($sar < $prevHigh) {
                        $sar = $prevHigh;
                    }
                    if ($sar < $newHigh) {
                        $sar = $newHigh;
                    }
                }
            }
        }
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function sma(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 30;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }

        return $this->TA_INT_SMA(
            $startIdx, $endIdx,
            $inReal, $optInTimePeriod,
            $outBegIdx, $outNBElement, $outReal
        );
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param float     $optInVFactor
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function t3(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, float $optInVFactor, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //int $outIdx, $lookbackTotal;
        //int $today, $i;
        //double $k, $one_minus_k;
        //double $e1, $e2, $e3, $e4, $e5, $e6;
        //double $c1, $c2, $c3, $c4;
        //double $tempReal;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 5;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        if ($optInVFactor == (-4e+37)) {
            $optInVFactor = 7.000000e-1;
        } elseif (($optInVFactor < 0.000000e+0) || ($optInVFactor > 1.000000e+0)) {
            return ReturnCode::BadParam;
        }
        $lookbackTotal = 6 * ($optInTimePeriod - 1) + ($this->unstablePeriod[UnstablePeriodFunctionID::T3]);
        if ($startIdx <= $lookbackTotal) {
            $startIdx = $lookbackTotal;
        }
        if ($startIdx > $endIdx) {
            $outNBElement->value = 0;
            $outBegIdx->value    = 0;

            return ReturnCode::Success;
        }
        $outBegIdx->value = $startIdx;
        $today            = $startIdx - $lookbackTotal;
        $k                = 2.0 / ($optInTimePeriod + 1.0);
        $one_minus_k      = 1.0 - $k;
        $tempReal         = $inReal[$today++];
        for ($i = $optInTimePeriod - 1; $i > 0; $i--) {
            $tempReal += $inReal[$today++];
        }
        $e1       = $tempReal / $optInTimePeriod;
        $tempReal = $e1;
        for ($i = $optInTimePeriod - 1; $i > 0; $i--) {
            $e1       = ($k * $inReal[$today++]) + ($one_minus_k * $e1);
            $tempReal += $e1;
        }
        $e2       = $tempReal / $optInTimePeriod;
        $tempReal = $e2;
        for ($i = $optInTimePeriod - 1; $i > 0; $i--) {
            $e1       = ($k * $inReal[$today++]) + ($one_minus_k * $e1);
            $e2       = ($k * $e1) + ($one_minus_k * $e2);
            $tempReal += $e2;
        }
        $e3       = $tempReal / $optInTimePeriod;
        $tempReal = $e3;
        for ($i = $optInTimePeriod - 1; $i > 0; $i--) {
            $e1       = ($k * $inReal[$today++]) + ($one_minus_k * $e1);
            $e2       = ($k * $e1) + ($one_minus_k * $e2);
            $e3       = ($k * $e2) + ($one_minus_k * $e3);
            $tempReal += $e3;
        }
        $e4       = $tempReal / $optInTimePeriod;
        $tempReal = $e4;
        for ($i = $optInTimePeriod - 1; $i > 0; $i--) {
            $e1       = ($k * $inReal[$today++]) + ($one_minus_k * $e1);
            $e2       = ($k * $e1) + ($one_minus_k * $e2);
            $e3       = ($k * $e2) + ($one_minus_k * $e3);
            $e4       = ($k * $e3) + ($one_minus_k * $e4);
            $tempReal += $e4;
        }
        $e5       = $tempReal / $optInTimePeriod;
        $tempReal = $e5;
        for ($i = $optInTimePeriod - 1; $i > 0; $i--) {
            $e1       = ($k * $inReal[$today++]) + ($one_minus_k * $e1);
            $e2       = ($k * $e1) + ($one_minus_k * $e2);
            $e3       = ($k * $e2) + ($one_minus_k * $e3);
            $e4       = ($k * $e3) + ($one_minus_k * $e4);
            $e5       = ($k * $e4) + ($one_minus_k * $e5);
            $tempReal += $e5;
        }
        $e6 = $tempReal / $optInTimePeriod;
        while ($today <= $startIdx) {
            $e1 = ($k * $inReal[$today++]) + ($one_minus_k * $e1);
            $e2 = ($k * $e1) + ($one_minus_k * $e2);
            $e3 = ($k * $e2) + ($one_minus_k * $e3);
            $e4 = ($k * $e3) + ($one_minus_k * $e4);
            $e5 = ($k * $e4) + ($one_minus_k * $e5);
            $e6 = ($k * $e5) + ($one_minus_k * $e6);
        }
        $tempReal           = $optInVFactor * $optInVFactor;
        $c1                 = -($tempReal * $optInVFactor);
        $c2                 = 3.0 * ($tempReal - $c1);
        $c3                 = -6.0 * $tempReal - 3.0 * ($optInVFactor - $c1);
        $c4                 = 1.0 + 3.0 * $optInVFactor - $c1 + 3.0 * $tempReal;
        $outIdx             = 0;
        $outReal[$outIdx++] = $c1 * $e6 + $c2 * $e5 + $c3 * $e4 + $c4 * $e3;
        while ($today <= $endIdx) {
            $e1                 = ($k * $inReal[$today++]) + ($one_minus_k * $e1);
            $e2                 = ($k * $e1) + ($one_minus_k * $e2);
            $e3                 = ($k * $e2) + ($one_minus_k * $e3);
            $e4                 = ($k * $e3) + ($one_minus_k * $e4);
            $e5                 = ($k * $e4) + ($one_minus_k * $e5);
            $e6                 = ($k * $e5) + ($one_minus_k * $e6);
            $outReal[$outIdx++] = $c1 * $e6 + $c2 * $e5 + $c3 * $e4 + $c4 * $e3;
        }
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function tema(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //double[] $firstEMA;
        //double[] $secondEMA;
        //double $k;
        $firstEMABegIdx     = new MyInteger();
        $firstEMANbElement  = new MyInteger();
        $secondEMABegIdx    = new MyInteger();
        $secondEMANbElement = new MyInteger();
        $thirdEMABegIdx     = new MyInteger();
        $thirdEMANbElement  = new MyInteger();
        //int $tempInt, $outIdx, $lookbackTotal, $lookbackEMA;
        //int $firstEMAIdx, $secondEMAIdx;
        //ReturnCode $ReturnCode;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 30;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        $outNBElement->value = 0;
        $outBegIdx->value    = 0;
        $lookbackEMA         = (new Lookback())->emaLookback($optInTimePeriod);
        $lookbackTotal       = $lookbackEMA * 3;
        if ($startIdx < $lookbackTotal) {
            $startIdx = $lookbackTotal;
        }
        if ($startIdx > $endIdx) {
            return ReturnCode::Success;
        }
        $tempInt    = $lookbackTotal + ($endIdx - $startIdx) + 1;
        $firstEMA   = $this->double($tempInt);
        $k          = ((double)2.0 / ((double)($optInTimePeriod + 1)));
        $ReturnCode = $this->TA_INT_EMA(
            $startIdx - ($lookbackEMA * 2), $endIdx, $inReal,
            $optInTimePeriod, $k,
            $firstEMABegIdx, $firstEMANbElement,
            $firstEMA
        );
        if (($ReturnCode != ReturnCode::Success) || ($firstEMANbElement->value == 0)) {
            return $ReturnCode;
        }
        $secondEMA  = $this->double($firstEMANbElement->value);
        $ReturnCode = $this->TA_INT_EMA(
            0, $firstEMANbElement->value - 1, $firstEMA,
            $optInTimePeriod, $k,
            $secondEMABegIdx, $secondEMANbElement,
            $secondEMA
        );
        if (($ReturnCode != ReturnCode::Success) || ($secondEMANbElement->value == 0)) {
            return $ReturnCode;
        }
        $ReturnCode = $this->TA_INT_EMA(
            0, $secondEMANbElement->value - 1, $secondEMA,
            $optInTimePeriod, $k,
            $thirdEMABegIdx, $thirdEMANbElement,
            $outReal
        );
        if (($ReturnCode != ReturnCode::Success) || ($thirdEMANbElement->value == 0)) {
            return $ReturnCode;
        }
        $firstEMAIdx      = $thirdEMABegIdx->value + $secondEMABegIdx->value;
        $secondEMAIdx     = $thirdEMABegIdx->value;
        $outBegIdx->value = $firstEMAIdx + $firstEMABegIdx->value;
        $outIdx           = 0;
        while ($outIdx < $thirdEMANbElement->value) {
            $outReal[$outIdx] += (3.0 * $firstEMA[$firstEMAIdx++]) - (3.0 * $secondEMA[$secondEMAIdx++]);
            $outIdx++;
        }
        $outNBElement->value = $outIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function trima(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //int $lookbackTotal;
        //double $numerator;
        //double $numeratorSub;
        //double $numeratorAdd;
        //int $i, $outIdx, $todayIdx, $trailingIdx, $middleIdx;
        //double $factor, $tempReal;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 30;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        $lookbackTotal = ($optInTimePeriod - 1);
        if ($startIdx < $lookbackTotal) {
            $startIdx = $lookbackTotal;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        $outIdx = 0;
        if (($optInTimePeriod % 2) == 1) {
            $i            = ($optInTimePeriod >> 1);
            $factor       = ($i + 1) * ($i + 1);
            $factor       = 1.0 / $factor;
            $trailingIdx  = $startIdx - $lookbackTotal;
            $middleIdx    = $trailingIdx + $i;
            $todayIdx     = $middleIdx + $i;
            $numerator    = 0.0;
            $numeratorSub = 0.0;
            for ($i = $middleIdx; $i >= $trailingIdx; $i--) {
                $tempReal     = $inReal[$i];
                $numeratorSub += $tempReal;
                $numerator    += $numeratorSub;
            }
            $numeratorAdd = 0.0;
            $middleIdx++;
            for ($i = $middleIdx; $i <= $todayIdx; $i++) {
                $tempReal     = $inReal[$i];
                $numeratorAdd += $tempReal;
                $numerator    += $numeratorAdd;
            }
            $outIdx             = 0;
            $tempReal           = $inReal[$trailingIdx++];
            $outReal[$outIdx++] = $numerator * $factor;
            $todayIdx++;
            while ($todayIdx <= $endIdx) {
                $numerator          -= $numeratorSub;
                $numeratorSub       -= $tempReal;
                $tempReal           = $inReal[$middleIdx++];
                $numeratorSub       += $tempReal;
                $numerator          += $numeratorAdd;
                $numeratorAdd       -= $tempReal;
                $tempReal           = $inReal[$todayIdx++];
                $numeratorAdd       += $tempReal;
                $numerator          += $tempReal;
                $tempReal           = $inReal[$trailingIdx++];
                $outReal[$outIdx++] = $numerator * $factor;
            }
        } else {
            $i            = ($optInTimePeriod >> 1);
            $factor       = $i * ($i + 1);
            $factor       = 1.0 / $factor;
            $trailingIdx  = $startIdx - $lookbackTotal;
            $middleIdx    = $trailingIdx + $i - 1;
            $todayIdx     = $middleIdx + $i;
            $numerator    = 0.0;
            $numeratorSub = 0.0;
            for ($i = $middleIdx; $i >= $trailingIdx; $i--) {
                $tempReal     = $inReal[$i];
                $numeratorSub += $tempReal;
                $numerator    += $numeratorSub;
            }
            $numeratorAdd = 0.0;
            $middleIdx++;
            for ($i = $middleIdx; $i <= $todayIdx; $i++) {
                $tempReal     = $inReal[$i];
                $numeratorAdd += $tempReal;
                $numerator    += $numeratorAdd;
            }
            $outIdx             = 0;
            $tempReal           = $inReal[$trailingIdx++];
            $outReal[$outIdx++] = $numerator * $factor;
            $todayIdx++;
            while ($todayIdx <= $endIdx) {
                $numerator          -= $numeratorSub;
                $numeratorSub       -= $tempReal;
                $tempReal           = $inReal[$middleIdx++];
                $numeratorSub       += $tempReal;
                $numeratorAdd       -= $tempReal;
                $numerator          += $numeratorAdd;
                $tempReal           = $inReal[$todayIdx++];
                $numeratorAdd       += $tempReal;
                $numerator          += $tempReal;
                $tempReal           = $inReal[$trailingIdx++];
                $outReal[$outIdx++] = $numerator * $factor;
            }
        }
        $outNBElement->value = $outIdx;
        $outBegIdx->value    = $startIdx;

        return ReturnCode::Success;
    }

    /**
     * @param int       $startIdx
     * @param int       $endIdx
     * @param array     $inReal
     * @param int       $optInTimePeriod
     * @param MyInteger $outBegIdx
     * @param MyInteger $outNBElement
     * @param array     $outReal
     *
     * @return int
     */
    public function wma(int $startIdx, int $endIdx, array $inReal, int $optInTimePeriod, MyInteger &$outBegIdx, MyInteger &$outNBElement, array &$outReal): int
    {
        //int $inIdx, $outIdx, $i, $trailingIdx, $divider;
        //double $periodSum, $periodSub, $tempReal, $trailingValue;
        //int $lookbackTotal;
        if ($startIdx < 0) {
            return ReturnCode::OutOfRangeStartIndex;
        }
        if (($endIdx < 0) || ($endIdx < $startIdx)) {
            return ReturnCode::OutOfRangeEndIndex;
        }
        if ((int)$optInTimePeriod == (PHP_INT_MIN)) {
            $optInTimePeriod = 30;
        } elseif (((int)$optInTimePeriod < 2) || ((int)$optInTimePeriod > 100000)) {
            return ReturnCode::BadParam;
        }
        $lookbackTotal = $optInTimePeriod - 1;
        if ($startIdx < $lookbackTotal) {
            $startIdx = $lookbackTotal;
        }
        if ($startIdx > $endIdx) {
            $outBegIdx->value    = 0;
            $outNBElement->value = 0;

            return ReturnCode::Success;
        }
        if ($optInTimePeriod == 1) {
            $outBegIdx->value    = $startIdx;
            $outNBElement->value = $endIdx - $startIdx + 1;
            //System::arraycopy($inReal, $startIdx, $outReal, 0, (int)$outNBElement->value);
            $outReal = \array_slice($inReal, $startIdx, (int)$outNBElement->value);

            return ReturnCode::Success;
        }
        $divider     = ($optInTimePeriod * ($optInTimePeriod + 1)) >> 1;
        $outIdx      = 0;
        $trailingIdx = $startIdx - $lookbackTotal;
        $periodSum   = $periodSub = (double)0.0;
        $inIdx       = $trailingIdx;
        $i           = 1;
        while ($inIdx < $startIdx) {
            $tempReal  = $inReal[$inIdx++];
            $periodSub += $tempReal;
            $periodSum += $tempReal * $i;
            $i++;
        }
        $trailingValue = 0.0;
        while ($inIdx <= $endIdx) {
            $tempReal           = $inReal[$inIdx++];
            $periodSub          += $tempReal;
            $periodSub          -= $trailingValue;
            $periodSum          += $tempReal * $optInTimePeriod;
            $trailingValue      = $inReal[$trailingIdx++];
            $outReal[$outIdx++] = $periodSum / $divider;
            $periodSum          -= $periodSub;
        }
        $outNBElement->value = $outIdx;
        $outBegIdx->value    = $startIdx;

        return ReturnCode::Success;
    }
}
