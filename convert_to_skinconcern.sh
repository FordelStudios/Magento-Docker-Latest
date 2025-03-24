#!/bin/bash

# Define base directories
BASE_DIR="src/app/code/Formula"
SKINTYPE_DIR="${BASE_DIR}/SkinType"
SKINCONCERN_DIR="${BASE_DIR}/SkinConcern"

# Remove existing SkinConcern directory if it exists
if [ -d "$SKINCONCERN_DIR" ]; then
    rm -rf "$SKINCONCERN_DIR"
    echo "Removed existing SkinConcern directory"
fi

# Create SkinConcern module base directory
mkdir -p "$SKINCONCERN_DIR"
echo "Created new SkinConcern directory"

# Function to copy and modify a file
copy_and_modify_file() {
    local src_file=$1
    local relative_path=$2
    
    # Create the new relative path by replacing SkinType with SkinConcern
    local new_relative_path=$(echo "$relative_path" | sed -e 's/SkinType/SkinConcern/g' -e 's/skintype/skinconcern/g')
    
    # Create the destination file path
    local dest_file="${SKINCONCERN_DIR}/${new_relative_path}"
    
    # Create destination directory if it doesn't exist
    mkdir -p "$(dirname "$dest_file")"
    
    # Copy and replace content in one step
    sed -e 's/SkinType/SkinConcern/g' \
        -e 's/skintype/skinconcern/g' \
        -e 's/SKINTYPE/SKINCONCERN/g' \
        -e 's/formula_skintype/formula_skinconcern/g' \
        "$src_file" > "$dest_file"
    
    echo "Created: $dest_file with modified content"
}

# Process all files in the SkinType directory
find "$SKINTYPE_DIR" -type f | while read src_file; do
    # Get the relative path from the SkinType directory
    relative_path="${src_file#$SKINTYPE_DIR/}"
    
    # Copy and modify the file
    copy_and_modify_file "$src_file" "$relative_path"
done

echo "SkinConcern module has been created successfully!"
echo "All files are placed in the correct directory structure."
echo ""
echo "Next steps:"
echo "1. Review the generated files"
echo "2. Update the db_schema.xml for SkinConcern-specific schema"
echo "3. Update the form and listing UI components"
echo "4. Configure the WebAPIs"