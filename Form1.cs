using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using System.IO;

namespace WindowsFormsApp1
{
    public partial class Form1 : Form
    {
        public Form1()
        {
            InitializeComponent();
        }

        private void Form1_Load(object sender, EventArgs e)
        {

        }

        #region buttonClick
        private async void submitButton_ClickAsync(object sender, EventArgs e)
        {
            //////////////////////////////
            var fileContent = string.Empty;
            var fExt = string.Empty;
            var filePath = string.Empty;
            var conversionType = string.Empty;
            var filenameWithoutPath = string.Empty;

            using (OpenFileDialog openFileDialog = new OpenFileDialog())
            {
                openFileDialog.InitialDirectory = "c:\\";
                openFileDialog.Filter = "txt files (*.txt)|*.txt|All files (*.*)|*.*";
                openFileDialog.FilterIndex = 2;
                openFileDialog.RestoreDirectory = true;

                if (openFileDialog.ShowDialog() == DialogResult.OK)
                {
                    fExt = Path.GetExtension(openFileDialog.FileName);
                    
                    //Get the path of specified file
                    filePath = openFileDialog.FileName;
                    filenameWithoutPath = Path.GetFileName(filePath);

                    textBox1.Text = filePath;
                    //Read the contents of the file into a stream
                    var fileStream = openFileDialog.OpenFile();

                    using (StreamReader reader = new StreamReader(fileStream))
                    {
                        fileContent = reader.ReadToEnd();
                        debugOutput(fileContent);
                    }
                    debugOutput(fExt);
                }
            }
            //////////////////////////////
            var cId = comboBox1.Text;
            conversionType = comboBox2.Text;
            debugOutput(cId);
            //RestClient rClient = new RestClient(cId, fileContent, fExt, "Account");
            RestClient rClient = new RestClient(cId, fileContent, fExt, filePath, conversionType, "api");
            rClient.endPoint = "http://127.0.0.1/efs/api/convertjson.php";
            debugOutput("Rest Client Created");

            //string strResponse = string.Empty;
            //strResponse = await rClient.makeFormRequestAsync();
            
            byte[] strResponse = await rClient.makeFormJsonRequestAsync();
            var str = System.Text.Encoding.Default.GetString(strResponse);
            str = str.Replace("\"", ""); //cleanup
            str = str.Replace("\\", ""); //cleanup
            debugOutput(str);

            //clientId = comboBox1.SelectedValue.ToString();
            //debugOutput(strResponse);
            // Example #2: Write one string to a text file.
            // WriteAllText creates a file, writes the specified string to the file,
            // and then closes the file.    You do NOT need to call Flush() or Close().
            //var dest = "C:\\Users\\keith\\source\\repos\\WindowsFormsApp1\\" + filePath + ".txt";
            //System.IO.File.WriteAllText(@dest, str);
            var convertedName = "converted_" + filenameWithoutPath;
            string path = Directory.GetCurrentDirectory();
            
            string[] paths = { @path, convertedName };
            string fullPath = Path.Combine(paths);
            debugOutput(fullPath);
            //debugOutput(filenameWithoutPath);

            //System.IO.File.WriteAllText(@"C:\Users\keith\source\repos\WindowsFormsApp1\WriteText.txt", str);
            System.IO.File.WriteAllText(@fullPath, str);
            label4.Text = "Export file created at:\\r\\n" + fullPath;

            //System.IO.File.WriteAllText(dest, str);

            //System.IO.File.WriteAllText(filePath, str);
            //string fileName = String.Format(@"{0}\type1.txt", Application.StartupPath);
        }

        private void debugOutput(string strDebugText)
        {
            try
            {
                System.Diagnostics.Debug.Write(strDebugText + Environment.NewLine);
                txtResponse.Text = txtResponse.Text + strDebugText + Environment.NewLine;
                txtResponse.SelectionStart = txtResponse.TextLength;
                txtResponse.ScrollToCaret();
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.Write(ex.Message, ToString() + Environment.NewLine);
            }
        }
        #endregion
    }
}
