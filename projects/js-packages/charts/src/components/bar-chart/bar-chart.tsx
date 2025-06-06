import { AxisLeft, AxisBottom } from '@visx/axis';
import { localPoint } from '@visx/event';
import { Group } from '@visx/group';
import { scaleBand, scaleLinear } from '@visx/scale';
import { Bar } from '@visx/shape';
import { useTooltip } from '@visx/tooltip';
import clsx from 'clsx';
import { FC, useCallback, type MouseEvent, ReactNode } from 'react';
import { useChartTheme } from '../../providers/theme';
import { GridControl } from '../grid-control';
import { Legend } from '../legend';
import { withResponsive } from '../shared/with-responsive';
import { BaseTooltip } from '../tooltip';
import styles from './bar-chart.module.scss';
import type { BaseChartProps, SeriesData } from '../../types';

type BarChartTooltipData = {
	value: number;
	xLabel: string;
	yLabel: string;
	seriesIndex: number;
};

interface BarChartProps extends BaseChartProps< SeriesData[] > {
	renderTooltip?: ( params: BarChartTooltipData ) => ReactNode;
}

const formatDateTick = ( timestamp: number ) => {
	const date = new Date( timestamp );
	return date.toLocaleDateString( undefined, {
		month: 'short',
		day: 'numeric',
	} );
};

// Default tooltip renderer
const renderDefaultTooltip = ( tooltipData: BarChartTooltipData ) => {
	if ( ! tooltipData ) return null;

	return (
		<div className={ styles[ 'bar-chart__tooltip' ] }>
			<div className={ styles[ 'bar-chart__tooltip-header' ] }>{ tooltipData.yLabel }</div>
			<div className={ styles[ 'bar-chart__tooltip-row' ] }>
				<span className={ styles[ 'bar-chart__tooltip-label' ] }>{ tooltipData.xLabel }:</span>
				<span className={ styles[ 'bar-chart__tooltip-value' ] }>{ tooltipData.value }</span>
			</div>
		</div>
	);
};

// Validation function similar to LineChart
const validateData = ( data: SeriesData[] ) => {
	if ( ! data?.length ) return 'No data available';

	const hasInvalidData = data.some( series =>
		series.data.some(
			d =>
				d.value === null ||
				d.value === undefined ||
				isNaN( d.value ) ||
				( ! d.label && ( ! d.date || isNaN( d.date.getTime() ) ) )
		)
	);

	if ( hasInvalidData ) return 'Invalid data';
	return null;
};

const BarChart: FC< BarChartProps > = ( {
	data,
	width,
	height = 400,
	className,
	margin = { top: 20, right: 20, bottom: 40, left: 40 },
	withTooltips = false,
	showLegend = false,
	legendOrientation = 'horizontal',
	gridVisibility = 'x',
	renderTooltip = renderDefaultTooltip,
	options = {},
} ) => {
	const theme = useChartTheme();
	const { tooltipOpen, tooltipLeft, tooltipTop, tooltipData, hideTooltip, showTooltip } =
		useTooltip< BarChartTooltipData >();
	// If we are to spread the options to axis, we need to get rid of tickFormat as it wouldn't work with band scales.
	const tickFormat = options.axis?.x?.tickFormat ?? formatDateTick;

	const handleMouseMove = useCallback(
		(
			event: MouseEvent< SVGRectElement >,
			value: number,
			xLabel: string,
			yLabel: string,
			seriesIndex: number
		) => {
			const coords = localPoint( event );
			if ( ! coords ) return;
			showTooltip( {
				tooltipData: { value, xLabel, yLabel, seriesIndex },
				tooltipLeft: coords.x,
				tooltipTop: coords.y - 10,
			} );
		},
		[ showTooltip ]
	);

	// Validate data using the same pattern as LineChart
	const error = validateData( data );
	if ( error ) {
		return <div className={ clsx( 'bar-chart', styles[ 'bar-chart' ] ) }>{ error }</div>;
	}

	const margins = margin;
	const xMax = width - margins.left - margins.right;
	const yMax = height - margins.top - margins.bottom;

	// Get labels for x-axis from the first series (assuming all series have same labels)
	const labels = data[ 0 ].data?.map( d => {
		return d?.label || tickFormat( d?.date );
	} );

	// Create scales
	const xScale = scaleBand< string >( {
		range: [ 0, xMax ],
		domain: labels,
		padding: 0.2,
	} );

	const innerScale = scaleBand( {
		range: [ 0, xScale.bandwidth() ],
		domain: data.map( ( _, i ) => i.toString() ),
		padding: 0.1,
	} );

	const yScale = scaleLinear< number >( {
		range: [ yMax, 0 ],
		domain: [
			0,
			Math.max( ...data.map( series => Math.max( ...series.data.map( d => d?.value || 0 ) ) ) ),
		],
	} );

	// Create legend items from group labels, this iterates over groups rather than data points
	const legendItems = data.map( ( group, index ) => ( {
		label: group.label, // Label for each unique group
		value: '', // Empty string since we don't want to show a specific value
		color: group.options?.stroke || theme.colors[ index % theme.colors.length ],
	} ) );

	return (
		<div
			className={ clsx( 'bar-chart', styles[ 'bar-chart' ], className ) }
			data-testid="bar-chart"
			role="img"
			aria-label="bar chart"
		>
			<svg width={ width } height={ height }>
				<Group left={ margins.left } top={ margins.top }>
					<GridControl
						width={ xMax }
						height={ yMax }
						xScale={ xScale }
						yScale={ yScale }
						gridVisibility={ gridVisibility }
					/>
					{ data.map( ( series, seriesIndex ) => (
						<Group key={ seriesIndex }>
							{ series.data.map( d => {
								const xLabel = d?.label || tickFormat( d?.date );
								const xPos = xScale( xLabel );
								if ( xPos === undefined ) return null;

								const barWidth = innerScale.bandwidth();
								const barX = xPos + ( innerScale( seriesIndex.toString() ) ?? 0 );
								const barColor =
									series.options?.stroke || theme.colors[ seriesIndex % theme.colors.length ];
								const handleBarMouseMove = ( event: MouseEvent< SVGRectElement > ) =>
									handleMouseMove( event, d.value, xLabel, series.label, seriesIndex );

								return (
									<Bar
										key={ `bar-${ seriesIndex }-${ xLabel }` }
										x={ barX }
										y={ yScale( d.value ) }
										width={ barWidth }
										height={ yMax - ( yScale( d.value ) ?? 0 ) }
										fill={ barColor }
										onMouseMove={ withTooltips ? handleBarMouseMove : undefined }
										onMouseLeave={ withTooltips ? hideTooltip : undefined }
									/>
								);
							} ) }
						</Group>
					) ) }
					<AxisLeft scale={ yScale } />
					<AxisBottom scale={ xScale } top={ yMax } />
				</Group>
			</svg>

			{ withTooltips && tooltipOpen && tooltipData && (
				<BaseTooltip top={ tooltipTop || 0 } left={ tooltipLeft || 0 }>
					{ renderTooltip( tooltipData ) }
				</BaseTooltip>
			) }

			{ showLegend && (
				<Legend
					items={ legendItems }
					orientation={ legendOrientation }
					className={ styles[ 'bar-chart__legend' ] }
				/>
			) }
		</div>
	);
};

export default withResponsive< BarChartProps >( BarChart );
