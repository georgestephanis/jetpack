import React from 'react';
import styles from './style.module.scss';
import type { AdminSectionBaseProps } from '../types.ts';

/**
 * The wrapper component for a Hero Section to be used in admin pages.
 *
 * @param {AdminSectionBaseProps} props - Component properties.
 * @return {React.Component} AdminSectionHero component.
 */
const AdminSectionHero: React.FC< AdminSectionBaseProps > = ( { children } ) => {
	return <div className={ styles[ 'section-hero' ] }>{ children }</div>;
};

export default AdminSectionHero;
