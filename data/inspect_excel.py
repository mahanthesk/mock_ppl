import pandas as pd

file_path = "data/Players Stats.xlsx"
xls = pd.ExcelFile(file_path)

for sheet in xls.sheet_names:
    df = pd.read_excel(xls, sheet_name=sheet, nrows=5)
    print(f"--- Sheet: {sheet} ---")
    print("Columns:", list(df.columns))
    print("First row:", df.iloc[0].to_dict() if not df.empty else "Empty")
    print()
