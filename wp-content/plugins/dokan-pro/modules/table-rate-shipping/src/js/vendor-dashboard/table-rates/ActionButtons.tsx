import { Button } from '@getdokan/dokan-ui';
import { __ } from '@wordpress/i18n';

interface ActionButtonsProps {
    onAdd: () => void;
    onDuplicate: () => void;
    onSave: () => void;
    onDelete: () => void;
    isSaving: boolean;
    hasSelectedRows: boolean;
}

export const ActionButtons = ( {
    onAdd,
    onDuplicate,
    onSave,
    onDelete,
    isSaving,
    hasSelectedRows,
}: ActionButtonsProps ) => {
    return (
        <div className="flex justify-end gap-2 mt-6">
            <Button
                onClick={ onAdd }
                className={ 'dokan-btn' }
                label={ __( 'Add Shipping Rate', 'dokan' ) }
            />
            <Button
                onClick={ onDuplicate }
                className={ 'dokan-btn' }
                disabled={ ! hasSelectedRows }
                label={ __( 'Duplicate Selected Rows', 'dokan' ) }
            />
            <Button
                onClick={ onSave }
                loading={ isSaving }
                className={ 'dokan-btn' }
                label={ __( 'Save Shipping Rates', 'dokan' ) }
            />
            <Button
                onClick={ onDelete }
                className={ 'dokan-btn' }
                disabled={ ! hasSelectedRows }
                label={ __( 'Delete Selected Rows', 'dokan' ) }
            />
        </div>
    );
};

export default ActionButtons;
