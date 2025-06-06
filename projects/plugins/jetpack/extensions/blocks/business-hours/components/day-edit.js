import { Button, TextControl, ToggleControl } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { isEmpty } from 'lodash';

const defaultOpen = '09:00';
const defaultClose = '17:00';

class DayEdit extends Component {
	renderInterval = ( interval, intervalIndex ) => {
		const { day } = this.props;
		const { opening, closing } = interval;
		return (
			<Fragment key={ intervalIndex }>
				<div className="business-hours__row">
					<div className={ clsx( day.name, 'business-hours__day' ) }>
						{ intervalIndex === 0 && this.renderDayToggle() }
					</div>
					<div className={ clsx( day.name, 'business-hours__hours' ) }>
						<TextControl
							__nextHasNoMarginBottom={ true }
							__next40pxDefaultSize
							type="time"
							label={ __( 'Opening', 'jetpack' ) }
							value={ opening }
							className="business-hours__open"
							placeholder={ defaultOpen }
							onChange={ value => {
								this.setHour( value, 'opening', intervalIndex );
							} }
						/>
						<TextControl
							__nextHasNoMarginBottom={ true }
							__next40pxDefaultSize
							type="time"
							label={ __( 'Closing', 'jetpack' ) }
							value={ closing }
							className="business-hours__close"
							placeholder={ defaultClose }
							onChange={ value => {
								this.setHour( value, 'closing', intervalIndex );
							} }
						/>
					</div>
					<div className="business-hours__remove">
						{ day.hours.length > 1 && (
							<Button
								isSmall
								variant="link"
								icon="trash"
								label={ __( 'Remove Hours', 'jetpack' ) }
								onClick={ () => {
									this.removeInterval( intervalIndex );
								} }
							/>
						) }
					</div>
				</div>
				{ intervalIndex === day.hours.length - 1 && (
					<div className="business-hours__row business-hours-row__add">
						<div className={ clsx( day.name, 'business-hours__day' ) }>&nbsp;</div>
						<div className={ clsx( day.name, 'business-hours__hours' ) }>
							<Button
								variant="link"
								label={ __( 'Add Hours', 'jetpack' ) }
								onClick={ this.addInterval }
							>
								{ __( 'Add Hours', 'jetpack' ) }
							</Button>
						</div>
						<div className="business-hours__remove">&nbsp;</div>
					</div>
				) }
			</Fragment>
		);
	};

	setHour = ( hourValue, hourType, hourIndex ) => {
		const { day, attributes, setAttributes } = this.props;
		const { days } = attributes;
		setAttributes( {
			days: days.map( value => {
				if ( value.name === day.name ) {
					return {
						...value,
						hours: value.hours.map( ( hour, index ) => {
							if ( index === hourIndex ) {
								return {
									...hour,
									[ hourType ]: hourValue,
								};
							}
							return hour;
						} ),
					};
				}
				return value;
			} ),
		} );
	};

	toggleClosed = nextValue => {
		const { day, attributes, setAttributes } = this.props;
		const { days } = attributes;

		setAttributes( {
			days: days.map( value => {
				if ( value.name === day.name ) {
					const hours = nextValue
						? [
								{
									opening: defaultOpen,
									closing: defaultClose,
								},
						  ]
						: [];
					return {
						...value,
						hours,
					};
				}
				return value;
			} ),
		} );
	};

	addInterval = () => {
		const { day, attributes, setAttributes } = this.props;
		const { days } = attributes;
		day.hours.push( { opening: '', closing: '' } );
		setAttributes( {
			days: days.map( value => {
				if ( value.name === day.name ) {
					return {
						...value,
						hours: day.hours,
					};
				}
				return value;
			} ),
		} );
	};

	removeInterval = hourIndex => {
		const { day, attributes, setAttributes } = this.props;
		const { days } = attributes;

		setAttributes( {
			days: days.map( value => {
				if ( day.name === value.name ) {
					return {
						...value,
						hours: value.hours.filter( ( hour, index ) => {
							return hourIndex !== index;
						} ),
					};
				}
				return value;
			} ),
		} );
	};

	isClosed() {
		const { day } = this.props;
		return isEmpty( day.hours );
	}

	renderDayToggle() {
		const { day, localization } = this.props;
		return (
			<Fragment>
				<span className="business-hours__day-name">{ localization.days[ day.name ] }</span>
				<ToggleControl
					__nextHasNoMarginBottom={ true }
					label={ this.isClosed() ? __( 'Closed', 'jetpack' ) : __( 'Open', 'jetpack' ) }
					checked={ ! this.isClosed() }
					onChange={ this.toggleClosed }
				/>
			</Fragment>
		);
	}

	renderClosed() {
		const { day } = this.props;
		return (
			<div className="business-hours__row business-hours-row__closed">
				<div className={ clsx( day.name, 'business-hours__day' ) }>{ this.renderDayToggle() }</div>
				<div className={ clsx( day.name, 'closed', 'business-hours__hours' ) }>&nbsp;</div>
				<div className="business-hours__remove">&nbsp;</div>
			</div>
		);
	}

	render() {
		const { day } = this.props;
		return this.isClosed() ? this.renderClosed() : day.hours.map( this.renderInterval );
	}
}

export default DayEdit;
