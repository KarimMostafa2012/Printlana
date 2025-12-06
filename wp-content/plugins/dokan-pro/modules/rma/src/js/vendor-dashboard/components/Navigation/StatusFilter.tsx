import { twMerge } from 'tailwind-merge';

import { useEffect } from '@wordpress/element';

import { StatusFilterSkeleton } from './StatusFilterSkeleton';
import { useStatusFilters } from '../../hooks/useStatusFilters';

interface StatusNavigationProps {
    statusParam: string;
    loadFilters?: boolean;
    onChange: ( status: string ) => void;
    onLoadComplete?: () => void;
}

// prettier-ignore
const StatusFilter: React.FC< StatusNavigationProps > = ( { statusParam, loadFilters = false, onChange, onLoadComplete = () => {}, } ) => {
    const { filters, isLoading, fetchFilters } = useStatusFilters();

    useEffect( () => {
        const doFetch = async () => {
            if ( loadFilters ) {
                await fetchFilters();
                onLoadComplete();
            }
        };

        void doFetch();
    }, [ loadFilters ] );

    if ( isLoading || ! filters ) {
        return <StatusFilterSkeleton />;
    }

    return (
        <nav className="flex items-center space-x-1" role="navigation">
            { filters.map( ( filter, index ) => (
                <div key={ `${ filter.name }-${ index }` } className="flex items-center">
                    <span
                        onClick={ () => onChange( filter.name ) }
                        className={ twMerge(
                            'text-dokan-link text-xs transition-all cursor-pointer',
                            statusParam === filter.name ? 'font-bold' : 'font-normal'
                        ) }
                        aria-current={
                            statusParam === filter.name ? 'page' : undefined
                        }
                        role="button"
                        tabIndex={ 0 }
                        onKeyDown={ ( e ) => {
                            if ( e.key === 'Enter' || e.key === ' ' ) {
                                onChange( filter.name );
                            }
                        } }
                    >
                        { filter.label }
                    </span>

                    { index < filters.length - 1 && (
                        <div className="border-r h-3 mx-1" aria-hidden="true" />
                    ) }
                </div>
            ) ) }
        </nav>
    );
};

export default StatusFilter;
