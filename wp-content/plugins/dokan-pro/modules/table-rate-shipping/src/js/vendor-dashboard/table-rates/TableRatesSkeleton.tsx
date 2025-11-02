const TableRatesSkeleton = (): JSX.Element => {
    return (
        <div className="mt-6 w-full space-y-6 animate-pulse">
            { /* Table Rates Section */ }
            <div className="space-y-4">
                { /* Rates Title Skeleton */ }
                <div className="space-y-4 mb-4">
                    <div className="h-4 w-full bg-gray-200 rounded animate-pulse" />
                    <div className="h-4 w-[80%] bg-gray-200 rounded animate-pulse" />
                </div>

                { /* Table Rates Skeleton */ }
                <div className="border border-gray-200 rounded-lg overflow-hidden">
                    { /* Table Header */ }
                    <div className="grid grid-cols-12 gap-1 p-2 bg-gray-50">
                        <div className="h-4 w-4 bg-gray-200 rounded" />
                        <div className="h-4 w-8 bg-gray-200 rounded" />
                        <div className="h-4 w-20 bg-gray-200 rounded" />
                        <div className="h-4 w-12 bg-gray-200 rounded" />
                        <div className="h-4 w-12 bg-gray-200 rounded" />
                        <div className="h-4 w-16 bg-gray-200 rounded" />
                        <div className="h-4 w-16 bg-gray-200 rounded" />
                        <div className="h-4 w-12 bg-gray-200 rounded" />
                        <div className="h-4 w-12 bg-gray-200 rounded" />
                        <div className="h-4 w-12 bg-gray-200 rounded" />
                        <div className="h-4 w-20 bg-gray-200 rounded" />
                        <div className="h-4 w-12 bg-gray-200 rounded" />
                    </div>

                    { /* Table Rows */ }
                    { [ 1, 2, 3, 4 ].map( ( row ) => (
                        <div
                            key={ row }
                            className="grid grid-cols-12 gap-1 p-2 border-t border-gray-200 items-center"
                        >
                            <div className="h-4 w-4 bg-gray-200 rounded" />
                            <div className="h-4 w-8 bg-gray-200 rounded" />
                            <div className="h-8 w-20 bg-gray-200 rounded" />
                            <div className="h-8 w-12 bg-gray-200 rounded" />
                            <div className="h-8 w-12 bg-gray-200 rounded" />
                            <div className="h-4 w-4 bg-gray-200 rounded" />
                            <div className="h-4 w-4 bg-gray-200 rounded" />
                            <div className="h-8 w-12 bg-gray-200 rounded" />
                            <div className="h-8 w-12 bg-gray-200 rounded" />
                            <div className="h-8 w-12 bg-gray-200 rounded" />
                            <div className="h-8 w-20 bg-gray-200 rounded" />
                            <div className="h-8 w-12 bg-gray-200 rounded" />
                        </div>
                    ) ) }
                </div>

                { /* Action Buttons Skeleton */ }
                <div className="flex space-x-4 mt-4 justify-end">
                    <div className="h-10 w-32 bg-gray-200 rounded" />
                    <div className="h-10 w-40 bg-gray-200 rounded" />
                    <div className="h-10 w-36 bg-gray-200 rounded" />
                    <div className="h-10 w-36 bg-gray-200 rounded" />
                </div>
            </div>
        </div>
    );
};

export default TableRatesSkeleton;
