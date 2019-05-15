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
            debugOutput(cId);
            //RestClient rClient = new RestClient(cId, fileContent, fExt, "Account");
            RestClient rClient = new RestClient(cId, fileContent, fExt, filePath, "Account");
            //rClient.endPoint = "https://jsonplaceholder.typicode.com/todos";
            //rClient.endPoint = "https://jsonplaceholder.typicode.com/posts";
            rClient.endPoint = "http://127.0.0.1/api/convert.php";
            debugOutput("Rest Client Created");
            

            string strResponse = string.Empty;
            strResponse = await rClient.makeFormRequestAsync();
            debugOutput(strResponse);

            //clientId = comboBox1.SelectedValue.ToString();
            debugOutput(strResponse);

            // Example #2: Write one string to a text file.
            string text = "A class is the most powerful data type in C#. Like a structure, " +
                           "a class defines the data and behavior of the data type. ";
            // WriteAllText creates a file, writes the specified string to the file,
            // and then closes the file.    You do NOT need to call Flush() or Close().
            System.IO.File.WriteAllText(@"C:\Users\keith\source\repos\WindowsFormsApp1\WriteText.txt", text);
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
